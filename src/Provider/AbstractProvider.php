<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Schema\Field\BooleanField;
use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\OrCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Search\Document;
use Contao\FrontendUser;
use Contao\Image\PictureConfiguration;
use Contao\StringUtil;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Terminal42\ContaoSeal\Provider\Exception\DocumentIgnoredException;

abstract class AbstractProvider implements ProviderInterface, ResponseModifyingProviderInterface
{
    protected const URI_DOCUMENT_PROPERTY = 'uri';

    private const MEMBER_GROUP_HEADER = 'contao-seal-member-group-hash';

    protected ContainerInterface|null $container = null;

    /**
     * @var array<int, string>
     */
    private array $searchableContentsPerDocument = [];

    /**
     * @param array<mixed> $providerConfig
     */
    public function __construct(
        private readonly array $providerConfig,
        private readonly GeneralProviderConfig $generalProviderConfig,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function getProviderConfig(): array
    {
        return $this->providerConfig;
    }

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;

        return $this;
    }

    public function modifyResponse(Request $request, Response $response): void
    {
        // Add the member group hash to the response so it's part of the response if there's a logged-in user.
        // This will allow to extract the information when indexing again as it is otherwise lost when the indexing
        // process is happening via Symfony Messenger.
        $groupHash = $this->getMemberGroupHash();

        if (null !== $groupHash) {
            $response->headers->set(self::MEMBER_GROUP_HEADER, $groupHash);
        }
    }

    public function getFieldsForSchema(): array
    {
        return array_merge(
            [
                self::URI_DOCUMENT_PROPERTY => new TextField(self::URI_DOCUMENT_PROPERTY, searchable: false, filterable: true, distinct: true), // Use URI as distinct to not show the same URI times
                'public' => new BooleanField('public', searchable: false, filterable: true),
                'groupHashes' => new TextField('groupHashes', searchable: false, multiple: true, filterable: true), // Access keys contain the member group combinations for a doc if not public
            ],
            $this->doGetFieldsForSchema(),
        );
    }

    public function getTemplateName(Request $request): string
    {
        return $this->generalProviderConfig->getTemplateName();
    }

    /**
     * @param ?array<string, mixed> $existingIndexedDocument Existing document with the matching document ID
     *
     * @return array<string, mixed>
     *
     * @throws DocumentIgnoredException In case the document should be ignored
     */
    public function convertDocumentToFields(Document $document, array|null $existingIndexedDocument): array
    {
        if (!Util::documentMatchesUrlRegex($document, $this->generalProviderConfig->getUrlRegex())) {
            throw DocumentIgnoredException::because('No URL regex matched.');
        }

        if (!Util::documentMatchesCanonicalRegex($document, $this->generalProviderConfig->getCanonicalRegex())) {
            throw DocumentIgnoredException::because('No Canonical URL regex matched.');
        }

        $contaoSchemaMeta = Util::extractContaoSchemaMeta($document);

        if ($this->skipDocumentsWithoutContaoSchemaData() && [] === $contaoSchemaMeta) {
            throw DocumentIgnoredException::because('No Contao JSON-LD schema was present on the document.');
        }

        // If search was disabled in the page settings, we do not index
        if (isset($contaoSchemaMeta['noSearch']) && true === $contaoSchemaMeta['noSearch']) {
            throw DocumentIgnoredException::because('The Contao JSON-LD schema contained "noSearch": true.');
        }

        // If the front end preview is activated, we do not index
        if (isset($contaoSchemaMeta['fePreview']) && true === $contaoSchemaMeta['fePreview']) {
            throw DocumentIgnoredException::because('The Contao JSON-LD schema contained "fePreview": true.');
        }

        $convertedDocument = [self::URI_DOCUMENT_PROPERTY => (string) $document->getUri()];

        // Read the member group hash from the document and not from the currently logged-in user. If indexing is
        // happening in the background via Symfony Messenger, there's never a logged-in user.
        $groupHash = $document->getHeaders()[self::MEMBER_GROUP_HEADER][0] ?? null;

        // Public document
        if (null === $groupHash) {
            $convertedDocument['public'] = true;
            $convertedDocument['groupHashes'] = []; // Reset, might have been marked protected before
        } else {
            $convertedDocument['public'] = false;
            $convertedDocument['groupHashes'] = [$groupHash];

            // If this document has been indexed before (same content on the same URL), we have to merge the access keys.
            // It might be that the content is exactly the same, even though a member is logged in. That means, we optimize
            // to not generate hundreds of duplicate search entries just because of the different permissions.
            if (null !== $existingIndexedDocument) {
                $convertedDocument['groupHashes'] = array_unique(array_merge($convertedDocument['groupHashes'], $existingIndexedDocument['groupHashes'] ?? []));
            }
        }

        return $this->doConvertDocumentToFields($document, $convertedDocument, $contaoSchemaMeta);
    }

    public function getDocumentId(Document $document): string
    {
        // One search entry per URI and document content hash
        return hash('xxh3', $document->getUri().'|'.$this->getDocumentContentHash($document));
    }

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array
    {
        $showNextLink = false;

        if ($this->isSubmitted($request)) {
            $groupHash = $this->getMemberGroupHash();

            if (null === $groupHash) {
                $searchBuilder->addFilter(new EqualCondition('public', true));
            } else {
                $searchBuilder->addFilter(new OrCondition(
                    new EqualCondition('public', true),
                    new AndCondition(
                        new EqualCondition('public', false),
                        new EqualCondition('groupHashes', $groupHash),
                    ),
                ));
            }

            $searchBuilder
                ->addFilter(new SearchCondition($this->getQuery($request)))
                ->distinct(self::URI_DOCUMENT_PROPERTY) // Only one result per URI, could match multiple due to public and matching group hashes
                ->limit($this->generalProviderConfig->getPerPage() + 1) // Request one more for the pagination
                ->offset($this->getOffset($request))
                ->highlight(
                    $this->getSearchableFields(),
                    \sprintf('<%s>', $this->generalProviderConfig->getHighlightTag()),
                    \sprintf('</%s>', $this->generalProviderConfig->getHighlightTag()),
                )
            ;

            $results = iterator_to_array($searchBuilder->getResult());

            if (\count($results) > $this->generalProviderConfig->getPerPage()) {
                $showNextLink = true;
                $results = \array_slice($results, 0, $this->generalProviderConfig->getPerPage());
            }
        } else {
            $results = [];
        }

        return array_merge($this->getDefaultTemplateData($request), [
            'results' => $this->formatResults($results),
            'pagination' => $this->getPagination($request, $showNextLink),
        ]);
    }

    protected function skipDocumentsWithoutContaoSchemaData(): bool
    {
        return true;
    }

    protected function getDocumentContentHash(Document $document): string
    {
        return hash('xxh3', $this->extractSearchableContentFromDocument($document));
    }

    protected function extractSearchableContentFromDocument(Document $document): string
    {
        // TODO: This method can be dropped once https://github.com/contao/contao/pull/8370 is available in the minimum required
        // version of Contao so we don't have a performance issue here anymore.
        $documentId = spl_object_id($document);

        // Pass true as second argument to Util::extractSearchableContentFromDocument() because we support indexing protected content elements
        return $this->searchableContentsPerDocument[$documentId] ?? $this->searchableContentsPerDocument[$documentId] = Util::extractSearchableContentFromDocument($document, true);
    }

    protected function getMemberGroupHash(): string|null
    {
        $user = $this->getFrontendUser();

        if (null === $user) {
            return null;
        }

        $groups = array_map('intval', StringUtil::deserialize($user->groups, true));

        sort($groups);

        return hash('xxh3', implode(',', $groups));
    }

    /**
     * @param array<array<string, mixed>> $results
     *
     * @return array<array<string, mixed>>
     */
    abstract protected function formatResults(array $results): array;

    /**
     * @return array{
     *     queryParam: string,
     *     page: int,
     *     query: string,
     *     offset: int,
     *     highlightTag: string,
     *     isSubmitted: bool
     * }
     */
    protected function getDefaultTemplateData(Request $request): array
    {
        return [
            'queryParam' => $this->generalProviderConfig->getQueryParameter(),
            'page' => $this->getCurrentPage($request),
            'query' => $this->getQuery($request),
            'offset' => $this->getOffset($request),
            'highlightTag' => $this->generalProviderConfig->getHighlightTag(),
            'isSubmitted' => $this->isSubmitted($request),
        ];
    }

    protected function getQuery(Request $request): string
    {
        return $request->query->get($this->generalProviderConfig->getQueryParameter(), '');
    }

    protected function getCurrentPage(Request $request): int
    {
        return $request->query->getInt($this->generalProviderConfig->getPageParameter(), 1);
    }

    protected function getOffset(Request $request): int
    {
        $currentPage = $this->getCurrentPage($request);
        $perPage = $this->generalProviderConfig->getPerPage();

        return ($currentPage - 1) * $perPage;
    }

    protected function isSubmitted(Request $request): bool
    {
        return '' !== $this->getQuery($request);
    }

    /**
     * @return array<string, mixed> $convertedDocument
     * @return array<string, mixed>
     */
    abstract protected function doConvertDocumentToFields(Document $document, array $convertedDocument, array $contaoSchemaOrgMeta): array;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getService(string $name): object
    {
        if (null === $this->container) {
            throw new \LogicException(\sprintf('Requested service "%s" in your provider but did not provide this service.', $name));
        }

        return $this->container->get($name);
    }

    protected function getUser(): UserInterface|null
    {
        try {
            return $this->getService('security.token_storage')->getToken()?->getUser();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function getFrontendUser(): FrontendUser|null
    {
        $user = $this->getUser();

        if (!$user instanceof FrontendUser) {
            return null;
        }

        return $user;
    }

    /**
     * @return array{
     *     page: int,
     *     previous_link: ?string,
     *     next_link: ?string,
     * }
     */
    protected function getPagination(Request $request, bool $showNextLink): array
    {
        $pagination = [
            'page' => $this->getCurrentPage($request),
            'previous_link' => null,
            'next_link' => null,
        ];

        if ($this->getCurrentPage($request) > 1) {
            $pagination['previous_link'] = Util::linkToPage($request, $this->generalProviderConfig->getPageParameter(), $this->getCurrentPage($request) - 1);
        }

        if ($showNextLink) {
            $pagination['next_link'] = Util::linkToPage($request, $this->generalProviderConfig->getPageParameter(), $this->getCurrentPage($request) + 1);
        }

        return $pagination;
    }

    /**
     * @param array{contentUrl: string, ...}                    $schemaOrgImageData
     * @param PictureConfiguration|array<mixed>|int|string|null $imageSize
     */
    protected function createFigureBuilderFromUrl(array $schemaOrgImageData, PictureConfiguration|array|int|string|null $imageSize = null): FigureBuilder|null
    {
        $imageSize ??= $this->generalProviderConfig->getImageSize();

        /** @var Studio $studio */
        $studio = $this->getService('contao.image.studio');
        /** @var RequestStack $requestStack */
        $requestStack = $this->getService('request_stack');
        /** @var ContaoContext $filesContext */
        $filesContext = $this->getService('contao.assets.files_context');

        $figureBuilder = $studio->createFigureBuilder()->fromUrl($schemaOrgImageData['contentUrl'], [
            $requestStack->getCurrentRequest()->getBaseUrl(),
            $filesContext->getStaticUrl(),
        ]);

        $figureMeta = new Metadata(array_filter([
            Metadata::VALUE_CAPTION => $schemaOrgImageData['caption'] ?? null,
            Metadata::VALUE_TITLE => $schemaOrgImageData['name'] ?? null,
            Metadata::VALUE_ALT => $schemaOrgImageData['alternateName'] ?? null,
        ]));

        return $figureBuilder->setSize($imageSize)->setMetadata($figureMeta);
    }

    /**
     * @param array<string, mixed> $document
     */
    protected function documentToContext(array $document): string
    {
        $context = [];

        foreach ($this->getSearchableFields() as $field) {
            if (!isset($document['_formatted'][$field]) || !\is_string($document['_formatted'][$field])) {
                continue;
            }

            $context[$field] = $document['_formatted'][$field];
        }

        return implode(' ', $context);
    }

    /**
     * @return array<string>
     */
    protected function getSearchableFields(): array
    {
        $fields = [];

        foreach ($this->getFieldsForSchema() as $field) {
            if ($field->searchable) {
                $fields[] = $field->name;
            }
        }

        return $fields;
    }

    /**
     * @return array<string, AbstractField>
     */
    abstract protected function doGetFieldsForSchema(): array;
}

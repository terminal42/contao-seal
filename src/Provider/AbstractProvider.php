<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Schema\Field\BooleanField;
use CmsIg\Seal\Schema\Field\IntegerField;
use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\InCondition;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected ContainerInterface|null $container = null;

    /**
     * @param array<mixed> $providerConfig
     */
    public function __construct(
        private array $providerConfig,
        private GeneralProviderConfig $generalProviderConfig,
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

    public function getFieldsForSchema(): array
    {
        return array_merge(
            [
                'protected' => new BooleanField('protected', filterable: true),
                'groups' => new IntegerField('groups', multiple: true, filterable: true),
            ],
            $this->doGetFieldsForSchema(),
        );
    }

    public function getTemplateName(Request $request): string
    {
        return $this->generalProviderConfig->getTemplateName();
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        if (!Util::documentMatchesUrlRegex($document, $this->generalProviderConfig->getUrlRegex())) {
            return null;
        }

        if (!Util::documentMatchesCanonicalRegex($document, $this->generalProviderConfig->getCanonicalRegex())) {
            return null;
        }

        $meta = Util::extractContaoSchemaMeta($document);

        // If search was disabled in the page settings, we do not index
        if (isset($meta['noSearch']) && true === $meta['noSearch']) {
            return null;
        }

        // If the front end preview is activated, we do not index
        if (isset($meta['fePreview']) && true === $meta['fePreview']) {
            return null;
        }

        $convertedDocument = ['groups' => [], 'protected' => false];

        if (isset($meta['protected'], $meta['groups']) && true === $meta['protected'] && \is_array($meta['groups'])) {
            $convertedDocument['groups'] = $meta['groups'];
            $convertedDocument['protected'] = true;
        }

        return $this->doConvertDocumentToFields($document, $convertedDocument, $meta);
    }

    public function getDocumentId(Document $document): string
    {
        return (string) $document->getUri();
    }

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array
    {
        $showNextLink = false;

        if ($this->isSubmitted($request)) {
            $user = $this->getFrontendUser();

            if (null === $user) {
                $searchBuilder->addFilter(new EqualCondition('protected', false));
            } else {
                $searchBuilder->addFilter(new OrCondition(
                    new EqualCondition('protected', false),
                    new AndCondition(
                        new EqualCondition('protected', true),
                        new InCondition('groups', array_map('intval', (array) $user->groups)),
                    ),
                ));
            }

            $searchBuilder
                ->addFilter(new SearchCondition($this->getQuery($request)))
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
        $imageSize = $imageSize ?? $this->generalProviderConfig->getImageSize();

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

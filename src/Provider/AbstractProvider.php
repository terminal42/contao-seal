<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Image\Studio\FigureBuilder;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Search\Document;
use Contao\Image\PictureConfiguration;
use Contao\Pagination;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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

        return $this->doConvertDocumentToFields($document);
    }

    public function getDocumentId(Document $document): string
    {
        return (string) $document->getUri();
    }

    protected function configureDefaultSearchBuilder(SearchBuilder $searchBuilder, Request $request): void
    {
        $searchBuilder
            ->addFilter(new SearchCondition($this->getQuery($request)))
            ->limit($this->generalProviderConfig->getPerPage())
            ->offset($this->getOffset($request))
            ->highlight(
                $this->getSearchableFields(),
                \sprintf('<%s>', $this->generalProviderConfig->getHighlightTag()),
                \sprintf('</%s>', $this->generalProviderConfig->getHighlightTag()),
            )
        ;
    }

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
            'previous_link' => null,
            'next_link' => null,
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
     * @return array<string, mixed>
     */
    abstract protected function doConvertDocumentToFields(Document $document): array;

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

    protected function getPagination(int $total): string
    {
        // TODO: convert to a more flexible, nicer solution once Contao migrates to a new Pagination logic
        // should be done in the template as well
        return (new Pagination($total, $this->generalProviderConfig->getPerPage(), 7, $this->generalProviderConfig->getPageParameter()))->generate("\n  ");
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
}

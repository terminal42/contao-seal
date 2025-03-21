<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Search\Document;
use Contao\Image\PictureConfiguration;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\EngineConfig;
use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider extends AbstractProvider implements ProviderInterface
{
    public function __construct(
        private readonly string $urlRegex,
        private readonly string $canonicalRegex,
        private readonly PictureConfiguration|array|int|string|null $imageSize,
    ) {
    }

    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'image' => new TextField('image', searchable: false),
        ];
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        if (!$this->documentMatchesUrlRegex($document, $this->urlRegex)) {
            return null;
        }

        if (!$this->documentMatchesCanonicalRegex($document, $this->canonicalRegex)) {
            return null;
        }

        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'image' => Util::extractPrimaryImageFromSchemaOrgData($document->extractJsonLdScripts()),
        ];
    }

    public function getTemplateName(Request $request): string
    {
        return '@Contao/frontend_search/standard.html.twig'; // TODO: dynamic
    }

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array
    {
        $queryParam = 'keywords'; // TODO: configurable in config?
        $query = $request->query->get($queryParam, '');
        $currentPageParam = 'page_s'; // TODO: append provider config id somehow
        //  $currentPage = $request->query->get($currentPageParam, 1); // TODO: use me
        $perPage = 10; // TODO: configurable?

        if ($query) {
            $result = $searchBuilder
                ->addFilter(new SearchCondition($query))
                ->highlight(['title', 'content'])
                ->limit(10)
                ->offset(0)
                ->getResult()
            ;
        } else {
            $result = Result::createEmpty();
        }

        return [
            'queryParam' => $queryParam,
            'query' => $query,
            'isSubmitted' => $request->query->has($queryParam),
            'results' => $this->formatResult($result),
            'pagination' => $this->getPagination($result->total(), $perPage, $currentPageParam),
        ];
    }

    private function formatResult(Result $result): array
    {
        $results = [];

        foreach ($result as $document) {
            $url = $document[EngineConfig::DOCUMENT_ID_ATTRIBUTE_NAME];
            $results[] = [
                'image' => $this->createFigureFromDocument($document, $url),
                'url' => $url,
                'title' => $document['title'],
                'context' => static::createFormattedContext($document),
            ];
        }

        return $results;
    }

    private function createFigureFromDocument(array $document, string $url): Figure|null
    {
        if (null === $this->imageSize) {
            return null;
        }

        if (!isset($document['image'])) {
            return null;
        }

        return $this->createFigureBuilderFromUrl($document['image'], $this->imageSize)
            ->setLinkHref($url)
            ->buildIfResourceExists()
        ;
    }
}

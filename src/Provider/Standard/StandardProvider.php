<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\EngineConfig;
use Terminal42\ContaoSeal\Provider\AbstractProvider;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider extends AbstractProvider
{
    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'image' => new TextField('image', searchable: false),
        ];
    }

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array
    {
        if ($this->isSubmitted($request)) {
            $this->configureDefaultSearchBuilder($searchBuilder, $request);
            $result = $searchBuilder->getResult();
        } else {
            $result = Result::createEmpty();
        }

        return array_merge($this->getDefaultTemplateData($request), [
            'results' => $this->formatResult($result),
            'pagination' => $this->getPagination($result->total()),
        ]);
    }

    protected function doConvertDocumentToFields(Document $document): array
    {
        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'image' => Util::extractPrimaryImageFromSchemaOrgData($document->extractJsonLdScripts()),
        ];
    }

    /**
     * @return array<int, array{
     *     image: Figure|null,
     *     url: string,
     *     title: string,
     *     context: string
     * }>
     */
    private function formatResult(Result $result): array
    {
        $results = [];

        foreach ($result as $document) {
            $url = $document[EngineConfig::DOCUMENT_ID_ATTRIBUTE_NAME];
            $results[] = [
                'image' => $this->createFigureFromDocument($document, $url),
                'url' => $url,
                'title' => $document['title'],
                'context' => $this->documentToContext($document),
            ];
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $document
     */
    private function createFigureFromDocument(array $document, string $url): Figure|null
    {
        if (!isset($document['image'])) {
            return null;
        }

        return $this->createFigureBuilderFromUrl($document['image'])
            ->setLinkHref($url)
            ->buildIfResourceExists()
        ;
    }
}

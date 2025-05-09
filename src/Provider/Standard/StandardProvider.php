<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Search\Document;
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

    protected function doConvertDocumentToFields(Document $document): array
    {
        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'image' => Util::extractPrimaryImageFromSchemaOrgData($document->extractJsonLdScripts()),
        ];
    }

    /**
     * @param array<array<string, mixed>> $results
     *
     * @return array<int, array{
     *     image: Figure|null,
     *     url: string,
     *     title: string,
     *     context: string
     * }>
     */
    protected function formatResults(array $results): array
    {
        $formatted = [];

        foreach ($results as $document) {
            $url = $document[EngineConfig::DOCUMENT_ID_ATTRIBUTE_NAME];
            $formatted[] = [
                'image' => $this->createFigureFromDocument($document, $url),
                'url' => $url,
                'title' => $document['title'],
                'context' => $this->documentToContext($document),
            ];
        }

        return $formatted;
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

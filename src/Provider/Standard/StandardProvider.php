<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Schema\Field\TextField;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Search\Document;
use Terminal42\ContaoSeal\Provider\AbstractProvider;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider extends AbstractProvider
{
    /**
     * @return array<string, AbstractField>
     */
    protected function doGetFieldsForSchema(): array
    {
        return [
            'uri' => new TextField('title', searchable: false),
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'image' => new TextField('image', searchable: false),
        ];
    }

    protected function doConvertDocumentToFields(Document $document, array $convertedDocument, array $contaoSchemaOrgMeta): array
    {
        return array_merge($convertedDocument, [
            'uri' => (string) $document->getUri(),
            'title' => Util::extractTitleFromDocument($document),
            'content' => $this->extractSearchableContentFromDocument($document),
            'image' => Util::extractPrimaryImageFromSchemaOrgData($document->extractJsonLdScripts()),
        ]);
    }

    /**
     * @param array<array<string, mixed>> $results
     *
     * @return array<int, array{
     *     uri: string,
     *     image: Figure|null,
     *     title: string,
     *     context: string
     * }>
     */
    protected function formatResults(array $results): array
    {
        $formatted = [];

        foreach ($results as $document) {
            $formatted[] = [
                'image' => $this->createFigureFromDocument($document, $document['uri']),
                'uri' => $document['uri'],
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

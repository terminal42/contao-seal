<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Schema\Field\TextField;
use Contao\CoreBundle\Search\Document;
use Terminal42\ContaoSeal\Indexer\Util;

class StandardProvider implements ProviderInterface
{
    public static function getName(): string
    {
        return 'standard';
    }

    /**
     * @return array<string, AbstractField>
     */
    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'meta' => new TextField('meta', searchable: false),
        ];
    }

    public function convertDocumentToFields(Document $document): array
    {
        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'meta' => json_encode($document->extractJsonLdScripts()),
        ];
    }
}

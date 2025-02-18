<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use Contao\CoreBundle\Search\Document;
use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider implements ProviderInterface
{
    private string $urlRegex;

    private string $canonicalRegex;

    public function __construct(private array $providerConfig)
    {
        $this->urlRegex = Util::buildRegexFromListWizard($this->providerConfig['urls'] ?? '');
        $this->canonicalRegex = Util::buildRegexFromListWizard($this->providerConfig['canonicals'] ?? '');
    }

    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'meta' => new TextField('meta', searchable: false),
        ];
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        if ($this->canonicalRegex) {
            $canonical = (string) $document->extractCanonicalUri();

            if ($canonical && !preg_match($this->canonicalRegex, $canonical)) {
                return null;
            }
        }

        if ($this->urlRegex) {
            $url = (string) $document->getUri();

            if (!preg_match($this->urlRegex, $url)) {
                return null;
            }
        }

        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'meta' => json_encode($document->extractJsonLdScripts()),
        ];
    }
}

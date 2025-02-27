<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;

interface ProviderInterface
{
    /**
     * @return array<string, AbstractField>
     */
    public function getFieldsForSchema(): array;

    /**
     * @return ?array<string, mixed> Return null if this document should be ignored
     */
    public function convertDocumentToFields(Document $document): array|null;

    public function getTemplateName(Request $request): string;

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array;

    public function getDocumentId(Document $document): string;
}

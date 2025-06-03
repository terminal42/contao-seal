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
     * @return array<mixed>
     */
    public function getProviderConfig(): array;

    /**
     * @return array<string, AbstractField>
     */
    public function getFieldsForSchema(): array;

    /**
     * @param ?array<string, mixed> $existingIndexedDocument Existing document with the matching document ID
     *
     * @return ?array<string, mixed> Return null if this document should be ignored (or if existing, deleted)
     */
    public function convertDocumentToFields(Document $document, array|null $existingIndexedDocument): array|null;

    public function getTemplateName(Request $request): string;

    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array;

    public function getDocumentId(Document $document): string;
}

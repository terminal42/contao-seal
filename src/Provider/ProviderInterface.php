<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\Provider\Exception\DocumentIgnoredException;

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
     * @return array<string, mixed>
     *
     * @throws DocumentIgnoredException In case the document should be ignored
     */
    public function convertDocumentToFields(Document $document, array|null $existingIndexedDocument): array;

    public function getTemplateName(Request $request): string;

    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array;

    public function getDocumentId(Document $document): string;
}

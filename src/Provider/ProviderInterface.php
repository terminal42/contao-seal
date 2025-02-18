<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use Contao\CoreBundle\Search\Document;

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
}

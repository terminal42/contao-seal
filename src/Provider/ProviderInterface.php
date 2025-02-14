<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use CmsIg\Seal\Schema\Field\AbstractField;
use Contao\CoreBundle\Search\Document;

interface ProviderInterface
{
    public static function getName(): string;

    /**
     * @return array<string, AbstractField>
     */
    public function getFieldsForSchema(): array;

    /**
     * @return array<string, mixed>
     */
    public function convertDocumentToFields(Document $document): array;
}

<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use Contao\CoreBundle\Search\Document;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class EngineConfig
{
    private EngineInterface|null $engine = null;

    public function __construct(
        private string $id,
        private AdapterInterface $adapter,
        private ProviderInterface $provider,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getIndexName(): string
    {
        return 'contao_frontend_'.$this->id;
    }

    public function getEngine(): EngineInterface
    {
        if (null !== $this->engine) {
            return $this->engine;
        }

        $fields = $this->provider->getFieldsForSchema();
        $fields = array_merge($fields, ['uri' => new IdentifierField('uri')]); // "uri" is always our identifier

        return $this->engine = new Engine(
            $this->adapter,
            new Schema([$this->getIndexName() => new Index(
                $this->getIndexName(),
                $fields,
            )]),
        );
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        return $this->provider->convertDocumentToFields($document);
    }
}

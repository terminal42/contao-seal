<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class EngineConfig
{
    public const DOCUMENT_ID_ATTRIBUTE_NAME = 'document_id';

    public const DATABASE_CONFIG_PREFIX = 'db_';

    public const CONFIG_CONFIG_PREFIX = '_';

    private EngineInterface|null $engine = null;

    private ProviderInterface|null $provider = null;

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    private function __construct(
        private readonly string $id,
        private readonly string $name,
        private readonly string $adapterName,
        private readonly AdapterInterface $adapter,
        private readonly string $providerFactoryName,
        private readonly \Closure $providerClosure,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
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

        $fields = $this->getProvider()->getFieldsForSchema();
        $fields = array_merge($fields, [
            self::DOCUMENT_ID_ATTRIBUTE_NAME => new IdentifierField(self::DOCUMENT_ID_ATTRIBUTE_NAME)]);

        return $this->engine = new Engine(
            $this->adapter,
            new Schema([$this->getIndexName() => new Index(
                $this->getIndexName(),
                $fields,
            )]),
        );
    }

    /**
     * @param ?array<string, mixed> $existingIndexedDocument Existing document with the matching document ID
     *
     * * @return ?array<string, mixed> Return null if this document should be ignored (or if existing, deleted)
     */
    public function convertDocumentToFields(Document $document, array|null $existingIndexedDocument): array|null
    {
        return $this->getProvider()->convertDocumentToFields($document, $existingIndexedDocument);
    }

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    public static function createFromDatabase(string $id, string $name, string $adapterName, AdapterInterface $adapter, string $providerFactoryName, \Closure $providerClosure): self
    {
        return new self($id, $name, $adapterName, $adapter, $providerFactoryName, $providerClosure);
    }

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    public static function createFromConfig(string $id, string $name, string $adapterName, AdapterInterface $adapter, string $providerFactoryName, \Closure $providerClosure): self
    {
        return new self($id, $name, $adapterName, $adapter, $providerFactoryName, $providerClosure);
    }

    /**
     * @return array<string, mixed>
     */
    public function getTemplateData(Request $request): array
    {
        return $this->getProvider()->getTemplateData($this->createSearchBuilder(), $request);
    }

    public function createSearchBuilder(): SearchBuilder
    {
        return $this->getEngine()->createSearchBuilder($this->getIndexName());
    }

    public function getTemplateName(Request $request): string
    {
        return $this->getProvider()->getTemplateName($request);
    }

    public function getDocumentId(Document $document): string
    {
        return $this->getProvider()->getDocumentId($document);
    }

    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    public function getProviderFactoryName(): string
    {
        return $this->providerFactoryName;
    }

    public function getProvider(): ProviderInterface
    {
        if (null === $this->provider) {
            $this->provider = ($this->providerClosure)();
        }

        return $this->provider;
    }
}

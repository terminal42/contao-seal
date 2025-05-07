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
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class EngineConfig
{
    public const DOCUMENT_ID_ATTRIBUTE_NAME = 'document_id';

    private const DATABASE_CONFIG_PREFIX = 'db_';

    private const CONFIG_CONFIG_PREFIX = '_';

    private EngineInterface|null $engine = null;

    private ProviderInterface|null $provider = null;

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    private function __construct(
        private string $id,
        private string $name,
        private string $adapterName,
        private AdapterInterface $adapter,
        private string $providerFactoryName,
        private \Closure $providerClosure,
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

    public function convertDocumentToFields(Document $document): array|null
    {
        return $this->getProvider()->convertDocumentToFields($document);
    }

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    public static function createFromDatabase(int $id, string $name, string $adapterName, AdapterInterface $adapter, string $providerFactoryName, \Closure $providerClosure): self
    {
        return new self(self::DATABASE_CONFIG_PREFIX.$id, $name, $adapterName, $adapter, $providerFactoryName, $providerClosure);
    }

    /**
     * @param \Closure(): ProviderInterface $providerClosure
     */
    public static function createFromConfig(string $configName, string $name, string $adapterName, AdapterInterface $adapter, string $providerFactoryName, \Closure $providerClosure): self
    {
        return new self(self::CONFIG_CONFIG_PREFIX.$configName, $name, $adapterName, $adapter, $providerFactoryName, $providerClosure);
    }

    public function getTemplateData(Request $request): array
    {
        $searchBuilder = $this->getEngine()->createSearchBuilder($this->getIndexName());

        return $this->getProvider()->getTemplateData($searchBuilder, $request);
    }

    public function getTemplateName(Request $request): string
    {
        return $this->getProvider()->getTemplateName($request);
    }

    public function getDocumentId(Document $document)
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

    private function getProvider(): ProviderInterface
    {
        if (null === $this->provider) {
            $this->provider = ($this->providerClosure)();
        }

        return $this->provider;
    }
}

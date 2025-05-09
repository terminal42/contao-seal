<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use CmsIg\Seal\Adapter\AdapterInterface;
use Contao\CoreBundle\Search\Document;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoSeal\Provider\ProviderFactoryInterface;
use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Seal\EventDispatchingAdapter;

class FrontendSearch implements ResetInterface
{
    /**
     * @var array<string, EngineConfig>|null
     */
    private array|null $indexConfigs = null;

    /**
     * @var array<string, ProviderFactoryInterface>
     */
    private array $providerFactories;

    /**
     * @param array<mixed>                            $configs
     * @param array<string, AdapterInterface>         $adapters
     * @param array<string, ProviderFactoryInterface> $providerFactories
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly TranslatorInterface $translator,
        private readonly array $configs,
        private readonly array $adapters,
        iterable $providerFactories,
    ) {
        $this->providerFactories = iterator_to_array($providerFactories);
    }

    /**
     * @return array<string, mixed>
     */
    public function getProviderTemplateData(string $configId, Request $request): array
    {
        $config = $this->getEngineConfigForId($configId);

        return $config->getTemplateData($request);
    }

    public function getProviderTemplateName(string $configId, Request $request): string
    {
        $config = $this->getEngineConfigForId($configId);

        return $config->getTemplateName($request);
    }

    public function index(Document $document): void
    {
        foreach ($this->getEngineConfigs() as $config) {
            $converted = $config->convertDocumentToFields($document);

            if (null === $converted) {
                continue;
            }

            // Ensure the converted document always has the correct primary key
            $converted = array_merge($converted, [
                EngineConfig::DOCUMENT_ID_ATTRIBUTE_NAME => $config->getDocumentId($document),
            ]);

            $config->getEngine()->saveDocument($config->getIndexName(), $converted);
        }
    }

    public function delete(Document $document): void
    {
        foreach ($this->getEngineConfigs() as $config) {
            $config->getEngine()->deleteDocument($config->getIndexName(), $config->getDocumentId($document));
        }
    }

    public function clear(): void
    {
        foreach ($this->getEngineConfigs() as $config) {
            $config->getEngine()->dropIndex($config->getIndexName());
            $config->getEngine()->createIndex($config->getIndexName());
        }
    }

    public function reset(): void
    {
        $this->indexConfigs = null;
    }

    /**
     * @return array<string, EngineConfig>
     */
    public function getEngineConfigs(): array
    {
        /**
         * @return \Closure<ProviderInterface>
         */
        $createProviderClosure = function (string $providerFactoryName, array $providerConfig): \Closure {
            if (!isset($this->providerFactories[$providerFactoryName])) {
                throw new \InvalidArgumentException(\sprintf('Provider factory "%s" not found.', $providerFactoryName));
            }

            return fn (): ProviderInterface => $this->providerFactories[$providerFactoryName]->createProvider($providerConfig);
        };

        $getAdapter = function (string $adapterName, string $configId): AdapterInterface {
            if (!isset($this->adapters[$adapterName])) {
                throw new \InvalidArgumentException(\sprintf('Adapter "%s" not found.', $adapterName));
            }

            return new EventDispatchingAdapter(
                $this->adapters[$adapterName],
                $this->eventDispatcher,
                $configId,
            );
        };

        if (null === $this->indexConfigs) {
            $this->indexConfigs = [];

            foreach ($this->configs as $configName => $config) {
                $configId = EngineConfig::CONFIG_CONFIG_PREFIX.$configName;
                $adapterName = $config['adapter'];
                $providerFactoryName = $config['providerFactory'];

                $config = EngineConfig::createFromConfig(
                    $configId,
                    $this->translator->trans('tl_search_index_config.index.'.$configName, [], 'contao_tl_search_index_config'),
                    $adapterName,
                    $getAdapter($adapterName, $configName),
                    $providerFactoryName,
                    $createProviderClosure($providerFactoryName, $config['providerConfig']),
                );
                $this->indexConfigs[$config->getId()] = $config;
            }

            foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_search_index_config') as $row) {
                $configId = EngineConfig::DATABASE_CONFIG_PREFIX.((int) $row['id']);
                $name = $row['name'];
                $adapterName = $row['adapter'];
                $providerFactoryName = $row['providerFactory'];

                unset($row['id'], $row['name'], $row['adapter'], $row['provider']);

                $config = EngineConfig::createFromDatabase(
                    $configId,
                    $name,
                    $adapterName,
                    $getAdapter($adapterName, $configId),
                    $providerFactoryName,
                    $createProviderClosure($providerFactoryName, $row),
                );
                $this->indexConfigs[$config->getId()] = $config;
            }
        }

        return $this->indexConfigs;
    }

    /**
     * @throws \InvalidArgumentException If config does not exist
     */
    public function getEngineConfigForId(string $configId): EngineConfig
    {
        $config = $this->getEngineConfigs()[$configId] ?? null;

        if (null === $config) {
            throw new \InvalidArgumentException(\sprintf('Index config "%s" not found.', $configId));
        }

        return $config;
    }
}

<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Search\Document;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoSeal\Provider\ProviderFactoryInterface;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class FrontendSearch implements ResetInterface
{
    private array|null $indexConfigs = null;

    /**
     * @var array<string, ProviderFactoryInterface>
     */
    private array $providerFactories;

    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        /** @var<string, array<mixed>> */
        private readonly array $configs,
        /** @var array<string, AdapterInterface> */
        private readonly array $adapters,
        iterable $providerFactories,
    ) {
        $this->providerFactories = iterator_to_array($providerFactories);
    }

    public function search(string $configId, string $query): Result
    {
        $config = $this->getEngineConfigs()[$configId] ?? null;

        if (null === $config) {
            throw new \InvalidArgumentException(\sprintf('Index config "%s" not found.', $configId));
        }

        return $config->getEngine()->createSearchBuilder($config->getIndexName())
            ->addFilter(new SearchCondition($query))
            ->limit(10)
            ->offset(0)
            ->getResult()
        ;
    }

    public function index(Document $document): void
    {
        foreach ($this->getEngineConfigs() as $config) {
            $converted = $config->convertDocumentToFields($document);

            if (null === $converted) {
                continue;
            }

            // Ensure the converted document always has the URI as primary key
            $converted = array_merge($converted, [
                'uri' => (string) $document->getUri(),
            ]);

            $config->getEngine()->saveDocument($config->getIndexName(), $converted);
        }
    }

    public function delete(Document $document): void
    {
        foreach ($this->getEngineConfigs() as $config) {
            $config->getEngine()->deleteDocument($config->getIndexName(), (string) $document->getUri());
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

    #[AsCallback('tl_search_index_config', 'fields.adapter.options')]
    public function getConfigAdapterOptions(): array
    {
        $options = [];

        foreach (array_keys($this->adapters) as $name) {
            $options[$name] = $this->translator->trans('tl_search_index_config.adapters.'.$name, [], 'contao_tl_search_index_config');
        }

        return $options;
    }

    #[AsCallback('tl_search_index_config', 'fields.providerFactory.options')]
    public function getProviderFactoryOptions(): array
    {
        $options = [];

        foreach (array_keys($this->providerFactories) as $name) {
            $options[$name] = $this->translator->trans('tl_search_index_config.provider_factory.'.$name, [], 'contao_tl_search_index_config');
        }

        return $options;
    }

    #[AsCallback('tl_content', 'fields.search_index.options')]
    public function getIndexOptions(): array
    {
        $options = [];

        foreach ($this->getEngineConfigs() as $config) {
            $options[$config->getId()] = $config->getName();
        }

        return $options;
    }

    /**
     * @return array<string, EngineConfig>
     */
    private function getEngineConfigs(): array
    {
        $createProvider = function (string $providerFactoryName, array $providerConfig): ProviderInterface {
            if (!isset($this->providerFactories[$providerFactoryName])) {
                throw new \InvalidArgumentException(\sprintf('Provider factory "%s" not found.', $providerFactoryName));
            }

            return $this->providerFactories[$providerFactoryName]->createProvider($providerConfig);
        };

        $getAdapter = function (string $adapterName): AdapterInterface {
            if (!isset($this->adapters[$adapterName])) {
                throw new \InvalidArgumentException(\sprintf('Adapter "%s" not found.', $adapterName));
            }

            return $this->adapters[$adapterName];
        };

        if (null === $this->indexConfigs) {
            $this->indexConfigs = [];

            foreach ($this->configs as $configName => $config) {
                $config = EngineConfig::createFromConfig(
                    $configName,
                    $this->translator->trans('tl_search_index_config.index.'.$configName, [], 'contao_tl_search_index_config'),
                    $getAdapter($config['adapter']),
                    $createProvider($config['providerFactory'], $config['providerConfig']),
                );
                $this->indexConfigs[$config->getId()] = $config;
            }

            foreach ($this->connection->fetchAllAssociative('SELECT * FROM tl_search_index_config') as $row) {
                $id = (int) $row['id'];
                $name = $row['name'];
                $adapter = $getAdapter($row['adapter']);
                $providerFactoryName = $row['providerFactory'];

                unset($row['id'], $row['name'], $row['adapter'], $row['provider']);

                $config = EngineConfig::createFromDatabase(
                    $id,
                    $name,
                    $adapter,
                    $createProvider($providerFactoryName, $row),
                );
                $this->indexConfigs[$config->getId()] = $config;
            }
        }

        return $this->indexConfigs;
    }
}

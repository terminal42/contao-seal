<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Schema\Field\IdentifierField;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Search\Document;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Terminal42\ContaoSeal\Provider\ProviderInterface;

class FrontendSearch implements ResetInterface
{
    private array|null $indexConfigs = null;

    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers;

    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        /** @var array<string, AdapterInterface> */
        private readonly array $adapters,
        iterable $providers,
    ) {
        $this->providers = iterator_to_array($providers);
    }

    public function search(int $configId, string $query): Result
    {
        $config = $this->getAllIndexConfigs()[$configId] ?? null;

        if (null === $config) {
            throw new \InvalidArgumentException(\sprintf('Index config "%s" not found.', $configId));
        }

        return $this->getEngineForIndex($configId, $config)->createSearchBuilder($this->getIndexName($configId))
            ->addFilter(new SearchCondition($query))
            ->limit(10)
            ->offset(0)
            ->getResult()
        ;
    }

    public function index(Document $document): void
    {
        foreach ($this->getAllIndexConfigs() as $configId => $config) {
            $engine = $this->getEngineForIndex($configId, $config);
            $provider = $this->getProviderForConfig($config);

            $document = array_merge($provider->convertDocumentToFields($document), [
                'uri' => (string) $document->getUri(),
            ]);

            $engine->saveDocument($this->getIndexName($configId), $document);
        }
    }

    public function delete(Document $document): void
    {
        foreach ($this->getAllIndexConfigs() as $configId => $config) {
            $engine = $this->getEngineForIndex($configId, $config);
            $engine->deleteDocument($this->getIndexName($configId), (string) $document->getUri());
        }
    }

    public function clear(): void
    {
        foreach ($this->getAllIndexConfigs() as $configId => $config) {
            $engine = $this->getEngineForIndex($configId, $config);
            $engine->dropIndex($this->getIndexName($configId));
            $engine->createIndex($this->getIndexName($configId));
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

    #[AsCallback('tl_search_index_config', 'fields.provider.options')]
    public function getConfigProviderOptions(): array
    {
        $options = [];

        foreach (array_keys($this->providers) as $name) {
            $options[$name] = $this->translator->trans('tl_search_index_config.providers.'.$name, [], 'contao_tl_search_index_config');
        }

        return $options;
    }

    private function getEngineForIndex(int $configId, array $config): EngineInterface
    {
        if (!isset($this->adapters[$config['adapter']])) {
            throw new \InvalidArgumentException(\sprintf('Adapter "%s" not found.', $config['adapter']));
        }

        $fields = $this->getProviderForConfig($config)->getFieldsForSchema();
        $fields = array_merge($fields, ['uri' => new IdentifierField('uri')]); // "uri" is always our identifier

        return new Engine(
            $this->adapters[$config['adapter']],
            new Schema([$this->getIndexName($configId) => new Index(
                $this->getIndexName($configId),
                $fields,
            )]),
        );
    }

    private function getProviderForConfig(array $config): ProviderInterface
    {
        if (!isset($this->providers[$config['provider']])) {
            throw new \InvalidArgumentException(\sprintf('Provider "%s" not found.', $config['provider']));
        }

        return $this->providers[$config['provider']];
    }

    private function getIndexName(int $configId): string
    {
        return 'contao_frontend_'.$configId;
    }

    /**
     * @return array<int, array>
     */
    private function getAllIndexConfigs(): array
    {
        if (null === $this->indexConfigs) {
            $this->indexConfigs = $this->connection->fetchAllAssociativeIndexed('SELECT * FROM tl_search_index_config');
        }

        return $this->indexConfigs;
    }
}

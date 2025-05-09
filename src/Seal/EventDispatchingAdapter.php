<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Seal;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\IndexerInterface;
use CmsIg\Seal\Adapter\SchemaManagerInterface;
use CmsIg\Seal\Adapter\SearcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatchingAdapter implements AdapterInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $configId,
    ) {
    }

    public function getSchemaManager(): SchemaManagerInterface
    {
        return $this->adapter->getSchemaManager();
    }

    public function getIndexer(): IndexerInterface
    {
        return $this->adapter->getIndexer();
    }

    public function getSearcher(): SearcherInterface
    {
        return new EventDispatchingSearcher(
            $this->adapter->getSearcher(),
            $this->eventDispatcher,
            $this->configId,
        );
    }
}

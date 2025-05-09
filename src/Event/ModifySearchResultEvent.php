<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Event;

use CmsIg\Seal\Search\Search;

class ModifySearchResultEvent
{
    /**
     * @param array<array<string, mixed>> $results
     */
    public function __construct(
        private array $results,
        private readonly Search $search,
        private readonly string $configId,
    ) {
    }

    public function getSearch(): Search
    {
        return $this->search;
    }

    public function getConfigId(): string
    {
        return $this->configId;
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param array<array<string, mixed>> $results
     */
    public function setResults(array $results): self
    {
        $this->results = $results;

        return $this;
    }
}

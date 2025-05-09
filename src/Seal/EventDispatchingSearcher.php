<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Seal;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Terminal42\ContaoSeal\Event\ModifySearchResultEvent;

class EventDispatchingSearcher implements SearcherInterface
{
    public function __construct(
        readonly private SearcherInterface $searcher,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $configId,
    ) {
    }

    public function search(Search $search): Result
    {
        // Cannot go endless in this bundle my friend
        if (null === $search->limit) {
            $search = $this->createNewSearch($search, $search->offset, 50);
        }

        $finalResults = [];

        do {
            $result = $this->searcher->search($search);

            if (0 === $result->total()) {
                break;
            }

            $results = iterator_to_array($result);
            $event = new ModifySearchResultEvent($results, $search, $this->configId);
            $this->eventDispatcher->dispatch($event);
            $finalResults = array_merge($finalResults, $event->getResults());

            if (\count($finalResults) < $search->limit) {
                $search = $this->createNewSearch($search, $search->offset + $search->limit, $search->limit);
            }
        } while (\count($finalResults) < $search->limit);

        $arrayToGenerator = static function (array $results): \Generator {
            foreach ($results as $result) {
                yield $result;
            }
        };

        return new Result($arrayToGenerator($finalResults), \count($finalResults));
    }

    private function createNewSearch(Search $originalSearch, int $offset, int $limit): Search
    {
        return new Search(
            $originalSearch->index,
            $originalSearch->filters,
            $originalSearch->sortBys,
            $limit,
            $offset,
            $originalSearch->highlightFields,
            $originalSearch->highlightPreTag,
            $originalSearch->highlightPostTag,
        );
    }
}

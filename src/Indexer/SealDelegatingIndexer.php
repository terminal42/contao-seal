<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Indexer;

use Contao\CoreBundle\Search\Document;
use Contao\CoreBundle\Search\Indexer\IndexerInterface;
use Terminal42\ContaoSeal\FrontendSearch;

class SealDelegatingIndexer implements IndexerInterface
{
    public function __construct(private readonly FrontendSearch $frontendSearch)
    {
    }

    public function index(Document $document): void
    {
        $this->frontendSearch->index($document);
    }

    public function delete(Document $document): void
    {
        $this->frontendSearch->delete($document);
    }

    public function clear(): void
    {
        $this->frontendSearch->clear();
    }
}

<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use Contao\CoreBundle\Search\Document;
use Contao\Pagination;

abstract class AbstractProvider
{
    public function getDocumentId(Document $document): string
    {
        return (string) $document->getUri();
    }

    protected function getPagination(int $total, int $perPage, string $perPageParam): string
    {
        // TODO: convert to a more flexible, nicer solution once Contao migrates to a new Pagination logic
        return (new Pagination($total, $perPage, 7, $perPageParam))->generate("\n  ");
    }
}

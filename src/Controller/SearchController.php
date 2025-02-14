<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Controller;

use CmsIg\Seal\Search\Result;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Pagination;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\ContaoSeal\FrontendSearch;

#[AsContentElement('search')]
class SearchController extends AbstractContentElementController
{
    public function __construct(private readonly FrontendSearch $frontendSearch)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $searchIndex = (int) $model->search_index ?? 0;

        if (0 === $searchIndex) {
            return new Response();
        }

        $queryParam = $model->queryParam ?? 'keywords'; // TODO: configurable in model?
        $query = $request->query->get($queryParam, '');
        $currentPageParam = 'page_s'.$model->id;
        $currentPage = $request->query->get($currentPageParam, 1);
        $perPage = $model->perPage ?? 10;

        $result = $this->getResult($searchIndex, $query, $currentPage, $perPage);

        $template->queryParam = $queryParam;
        $template->query = $query;
        $template->isSubmitted = $request->query->has($queryParam);
        $template->results = $this->formatResults($result);

        $template->pagination = $this->getPagination($result->total(), $perPage, $currentPageParam);

        return $template->getResponse();
    }

    private function getResult(int $searchIndex, string $query, int $page, int $perPage): Result
    {
        if ('' === $query) {
            return new Result((
                static function (): \Generator {
                    yield from [];
                }
            )(), 0);
        }

        return $this->frontendSearch->search($searchIndex, $query);
    }

    private function getPagination(int $total, int $perPage, string $perPageParam): string
    {
        // TODO: convert to a more flexible, nicer solution once Contao migrates to a new Pagination logic
        return (new Pagination($total, $perPage, 7, $perPageParam))->generate("\n  ");
    }

    private function formatResults(Result $result): array
    {
        $results = [];

        foreach ($result as $document) {
            $results[] = [
                'image' => null,
                'url' => $document['uri'],
                'decodedUrl' => urldecode($document['uri']),
                'title' => $document['title'],
                'headline' => 'Headline!', // TODO: Wtf is this?
                'context' => $document['content'], // TODO: context, should be a SEAL feature
            ];
        }

        return $results;
    }
}

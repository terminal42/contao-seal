<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider extends AbstractProvider implements ProviderInterface
{
    private string $urlRegex;

    private string $canonicalRegex;

    public function __construct(private array $providerConfig)
    {
        $this->urlRegex = Util::buildRegexFromListWizard($this->providerConfig['urls'] ?? '');
        $this->canonicalRegex = Util::buildRegexFromListWizard($this->providerConfig['canonicals'] ?? '');
    }

    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'meta' => new TextField('meta', searchable: false),
        ];
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        if ($this->canonicalRegex) {
            $canonical = (string) $document->extractCanonicalUri();

            if ($canonical && !preg_match($this->canonicalRegex, $canonical)) {
                return null;
            }
        }

        if ($this->urlRegex) {
            $url = (string) $document->getUri();

            if (!preg_match($this->urlRegex, $url)) {
                return null;
            }
        }

        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'meta' => json_encode($document->extractJsonLdScripts()),
        ];
    }

    public function getTemplateName(Request $request): string
    {
        return '@Contao/frontend_search/standard.html.twig'; // TODO: dynamic
    }

    public function getTemplateData(SearchBuilder $searchBuilder, Request $request): array
    {
        $queryParam = 'keywords'; // TODO: configurable in config?
        $query = $request->query->get($queryParam, '');
        $currentPageParam = 'page_s'; // TODO: append provider config id somehow
        $currentPage = $request->query->get($currentPageParam, 1); // TODO: use me
        $perPage = 10; // TODO: configurable?

        $result = $searchBuilder
            ->addFilter(new SearchCondition($query))
            ->highlight(['title', 'content'])
            ->limit(10)
            ->offset(0)
            ->getResult()
        ;

        return [
            'queryParam' => $queryParam,
            'query' => $query,
            'isSubmitted' => $request->query->has($queryParam),
            'results' => $this->formatResults($result),
            'pagination' => $this->getPagination($result->total(), $perPage, $currentPageParam),
        ];
    }

    protected function formatResults(Result $result): array
    {
        $results = [];

        foreach ($result as $document) {
            $results[] = [
                'image' => null,
                'url' => $document['uri'],
                'decodedUrl' => urldecode($document['uri']),
                'title' => $document['title'],
                'headline' => $document['title'], // TODO: Need me?
                'context' => $this->createContext($document),
            ];
        }

        return $results;
    }

    private function createContext(array $document): string
    {
        if (!isset($document['_formatted'])) {
            return '';
        }

        $context = [];

        foreach ($document['_formatted'] as $field => $value) {
            $context[] = $value;
        }

        $context = implode(' ', $context);

        return Util::trimSearchContext($context, 50);
    }
}

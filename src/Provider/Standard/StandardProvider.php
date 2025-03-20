<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider\Standard;

use CmsIg\Seal\Schema\Field\TextField;
use CmsIg\Seal\Search\Condition\SearchCondition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\SearchBuilder;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Search\Document;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\ContaoSeal\EngineConfig;
use Terminal42\ContaoSeal\Provider\ProviderInterface;
use Terminal42\ContaoSeal\Provider\Util;

class StandardProvider extends AbstractProvider implements ProviderInterface
{
    private string $urlRegex;

    private string $canonicalRegex;

    public function __construct(private readonly array $providerConfig)
    {
        $this->urlRegex = Util::buildRegexFromListWizard($this->providerConfig['urls'] ?? '');
        $this->canonicalRegex = Util::buildRegexFromListWizard($this->providerConfig['canonicals'] ?? '');
    }

    public function getFieldsForSchema(): array
    {
        return [
            'title' => new TextField('title', searchable: true),
            'content' => new TextField('content', searchable: true),
            'image' => new TextField('image', searchable: false),
        ];
    }

    public function convertDocumentToFields(Document $document): array|null
    {
        //  TODO: Put me into abstract
        if ($this->canonicalRegex) {
            $canonical = (string) $document->extractCanonicalUri();

            if ($canonical && !preg_match($this->canonicalRegex, $canonical)) {
                return null;
            }
        }

        $url = (string) $document->getUri();

        //  TODO: Put me into abstract
        if ($this->urlRegex) {
            if (!preg_match($this->urlRegex, $url)) {
                return null;
            }
        }

        return [
            'title' => Util::extractTitleFromDocument($document),
            'content' => Util::extractSearchableContentFromDocument($document),
            'image' => Util::extractPrimaryImageFromSchemaOrgData($document->extractJsonLdScripts()),
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
        //  $currentPage = $request->query->get($currentPageParam, 1); // TODO: use me
        $perPage = 10; // TODO: configurable?

        if ($query) {
            $result = $searchBuilder
                ->addFilter(new SearchCondition($query))
                ->highlight(['title', 'content'])
                ->limit(10)
                ->offset(0)
                ->getResult()
            ;
        } else {
            $result = Result::createEmpty();
        }

        return [
            'queryParam' => $queryParam,
            'query' => $query,
            'isSubmitted' => $request->query->has($queryParam),
            'results' => $this->formatResult($result),
            'pagination' => $this->getPagination($result->total(), $perPage, $currentPageParam),
        ];
    }

    private function formatResult(Result $result): array
    {
        $results = [];

        foreach ($result as $document) {
            $url = $document[EngineConfig::DOCUMENT_ID_ATTRIBUTE_NAME];
            $results[] = [
                'image' => $this->createFigureFromDocument($document, $url),
                'url' => $url,
                'title' => $document['title'],
                'context' => static::createFormattedContext($document),
            ];
        }

        return $results;
    }

    private function createFigureFromDocument(array $document, string $url): Figure|null
    {
        if (!isset($this->providerConfig['imgSize'])) {
            return null;
        }

        if (!isset($document['image'])) {
            return null;
        }

        return $this->createFigureBuilderFromUrl($document['image'], $this->providerConfig['imgSize'])
            ->setLinkHref($url)
            ->buildIfResourceExists()
        ;
    }
}

<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Contao\CoreBundle\Search\Document;
use Contao\StringUtil;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class Util
{
    private const INDEXER_STOP = '<!-- indexer::stop -->';

    private const INDEXER_PROTECTED = '<!-- indexer::protected -->';

    private const INDEXER_CONTINUE = '<!-- indexer::continue -->';

    /**
     * @param array<mixed> $schemaData
     *
     * @return array{contentUrl: string, ...}|null
     */
    public static function extractPrimaryImageFromSchemaOrgData(array $schemaData): array|null
    {
        foreach ($schemaData as $v) {
            if (isset($v['https://schema.org/primaryImageOfPage']['contentUrl']) && \is_string($v['https://schema.org/primaryImageOfPage']['contentUrl'])) {
                return $v['https://schema.org/primaryImageOfPage'];
            }
        }

        return null;
    }

    public static function extractSearchableContentFromDocument(Document $document, bool $allowProtected = false): string
    {
        // We're only interested in <body>
        $body = $document->getContentCrawler()->filterXPath('//body');

        // No <body> found, abort
        if (0 === $body->count()) {
            return '';
        }

        // Remove <script> and <style> tags
        $body->filterXPath('//script | //style')->each(static fn (Crawler $node) => $node->getNode(0)->parentNode->removeChild($node->getNode(0)));

        // Extract the HTML and filter it for indexer start and stop comments
        $html = $body->html();

        while (($start = strpos($html, self::INDEXER_STOP)) !== false) {
            $afterStop = substr($html, $start + \strlen(self::INDEXER_STOP), \strlen(self::INDEXER_PROTECTED));

            // Skip removal if protected tag is immediately after stop tag and allowProtected is true
            if ($allowProtected && self::INDEXER_PROTECTED === $afterStop) {
                // Skip this and continue after this occurrence
                $start = strpos($html, self::INDEXER_STOP, $start + \strlen(self::INDEXER_STOP));
                if (false === $start) {
                    break;
                }
                continue;
            }

            if (($end = strpos($html, self::INDEXER_CONTINUE, $start)) !== false) {
                $current = $start;

                // Handle nested tags
                while (($nested = strpos($html, self::INDEXER_STOP, $current + \strlen(self::INDEXER_STOP))) !== false && $nested < $end) {
                    if (($newEnd = strpos($html, self::INDEXER_CONTINUE, $end + \strlen(self::INDEXER_CONTINUE))) !== false) {
                        $end = $newEnd;
                        $current = $nested;
                    } else {
                        break;
                    }
                }

                $html = substr($html, 0, $start).substr($html, $end + \strlen(self::INDEXER_CONTINUE));
            } else {
                break;
            }
        }

        // Strip HTML tags and cleanup
        $html = strip_tags($html);

        return trim((string) preg_replace(['/^[ \t]*$/m', '/\s+/'], ['', ' '], $html));
    }

    public static function extractTitleFromDocument(Document $document): string
    {
        try {
            return $document->getContentCrawler()->filterXPath('//head/title')->first()->text();
        } catch (\Exception) {
            return 'undefined';
        }
    }

    public static function extractContaoSchemaMeta(Document $document): array
    {
        $jsonLds = $document->extractJsonLdScripts('https://schema.contao.org/', 'Page');

        if (!$jsonLds) {
            return [];
        }

        return array_merge(...$jsonLds);
    }

    /**
     * @param array<string>|string $listWizard
     * ^ */
    public static function buildRegexFromListWizard(array|string $listWizard): string
    {
        $filter = array_filter(StringUtil::deserialize($listWizard, true));

        if ([] === $filter) {
            return '';
        }

        $regex = [];

        foreach ($filter as $value) {
            $regex[] = $value;
        }

        return '@'.implode('|', $regex).'@';
    }

    public static function documentMatchesUrlRegex(Document $document, string $regex): bool
    {
        if ('' === $regex) {
            return true;
        }

        $url = (string) $document->getUri();

        if ($url && !preg_match($regex, $url)) {
            return false;
        }

        return true;
    }

    public static function documentMatchesCanonicalRegex(Document $document, string $regex): bool
    {
        if ('' === $regex) {
            return true;
        }

        $canonical = (string) $document->extractCanonicalUri();

        if ($canonical && !preg_match($regex, $canonical)) {
            return false;
        }

        return true;
    }

    public static function linkToPage(Request $request, string $pageParameter, int $targetPage): string
    {
        $parts = parse_url($request->getUri());
        parse_str($parts['query'] ?? '', $query);

        unset($query[$pageParameter]);

        if ($targetPage > 1) {
            $query[$pageParameter] = $targetPage;
        }

        $queryString = http_build_query($query, '', '&');

        $scheme = $parts['scheme'] ?? 'http';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $auth = $user ? "$user$pass@" : '';
        $path = $parts['path'] ?? '';

        return "$scheme://$auth$host$port$path".($queryString ? "?$queryString" : '');
    }
}

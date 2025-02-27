<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Contao\CoreBundle\Search\Document;
use Contao\StringUtil;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

class Util
{
    public static function extractSearchableContentFromDocument(Document $document): string
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

        // Strip non-indexable areas
        while (($start = strpos($html, '<!-- indexer::stop -->')) !== false) {
            if (($end = strpos($html, '<!-- indexer::continue -->', $start)) !== false) {
                $current = $start;

                // Handle nested tags
                while (($nested = strpos($html, '<!-- indexer::stop -->', $current + 22)) !== false && $nested < $end) {
                    if (($newEnd = strpos($html, '<!-- indexer::continue -->', $end + 26)) !== false) {
                        $end = $newEnd;
                        $current = $nested;
                    } else {
                        break;
                    }
                }

                $html = substr($html, 0, $start).substr($html, $end + 26);
            } else {
                break;
            }
        }

        // Strip HTML tags and cleanup
        $html = strip_tags($html);

        return trim(preg_replace('/ +/', ' ', $html));
    }

    public static function extractTitleFromDocument(Document $document): string
    {
        try {
            return $document->getContentCrawler()->filterXPath('//head/title')->first()->text();
        } catch (\Exception) {
            return 'undefined';
        }
    }

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

    public static function trimSearchContext(string $context, int $numberOfContextChars, string $contextEllipsis = '[…]', string $preTag = '<mark>', string $postTag = '</mark>'): string
    {
        $chunks = [];
        $context = new UnicodeString($context);

        foreach ($context->split($preTag) as $chunk) {
            foreach ($chunk->split($postTag, 2) as $innerChunk) {
                $chunks[] = $innerChunk;
            }
        }

        // Iterate over odd indices (1, 3, 5, ...) which are the key phrases
        for ($i = 1; $i < \count($chunks); $i += 2) {
            // Truncate and prepend "..." to the previous entry (if truncated)
            if ($i - 1 >= 0) {
                $truncated = $chunks[$i - 1]->reverse()->truncate($numberOfContextChars, cut: false)->reverse();
                if (!$truncated->equalsTo($chunks[$i - 1])) {
                    $chunks[$i - 1] = $truncated->prepend($contextEllipsis);
                }
            }

            // Truncate and append "..." to the next entry (if truncated)
            if ($i + 1 < \count($chunks)) {
                $truncated = $chunks[$i + 1]->truncate($numberOfContextChars, cut: false);
                if (!$truncated->equalsTo($chunks[$i + 1])) {
                    $chunks[$i + 1] = $truncated->append($contextEllipsis);
                }
            }
        }

        $result = [];

        foreach ($chunks as $i => $chunk) {
            if (0 === $i % 2) {
                $result[] = $chunk->toString();
            } else {
                // Key phrase needs tags again
                $result[] = $chunk->prepend($preTag)->append($postTag)->toString();
            }
        }

        return implode('', $result);
    }
}

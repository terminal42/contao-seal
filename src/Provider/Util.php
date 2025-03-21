<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Contao\CoreBundle\Search\Document;
use Contao\StringUtil;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\String\UnicodeString;

class Util
{
    /**
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
        $context = new UnicodeString($context);
        $chunks = [];

        foreach ($context->split($preTag) as $chunk) {
            foreach ($chunk->split($postTag, 2) as $innerChunk) {
                $chunks[] = $innerChunk;
            }
        }

        if (\count($chunks) < 3 || 1 !== \count($chunks) % 2) {
            return $context->toString();
        }

        $trim = static function (UnicodeString $string, int $length, bool $fromEnd) use ($contextEllipsis): UnicodeString {
            $truncated = $fromEnd
                ? $string->reverse()->truncate($length, cut: false)->reverse()
                : $string->truncate($length, cut: false);

            if ($truncated->equalsTo($string)) {
                return $string;
            }

            return $fromEnd
                ? $truncated->prepend($contextEllipsis)
                : $truncated->append($contextEllipsis);
        };

        $result = [];

        foreach ($chunks as $i => $chunk) {
            // Even = context, Odd = highlighted key phrases
            if (0 === $i % 2) {
                // The first chunk only ever has to be prepended
                if (0 === $i) {
                    $result[] = $trim($chunk, $numberOfContextChars, true)->toString();
                    // The last chunk only ever has to be appended
                } elseif ($i === \count($chunks) - 1) {
                    $result[] = $trim($chunk, $numberOfContextChars, false)->toString();
                    // An in-between chunk has to be left untouched, if it is shorter or equal the desired context length
                } elseif ($chunk->length() <= $numberOfContextChars) {
                    $result[] = $chunk->toString();
                    // Otherwise we have to prepend and append
                } else {
                    $pre = $trim($chunk, $numberOfContextChars, true);
                    $post = $trim($chunk, $numberOfContextChars, false);

                    // If both have been shortened, we would have a double ellipsis now, so let's trim that
                    if ($post->endsWith($contextEllipsis) && $pre->startsWith($contextEllipsis)) {
                        $post = $post->trimSuffix($contextEllipsis);
                    }

                    $result[] = $post->append($pre->toString())->toString();
                }
            } else {
                // Highlighted chunk, leave that untouched with the tags
                $result[] = $chunk->prepend($preTag)->append($postTag)->toString();
            }
        }

        return implode('', $result);
    }
}

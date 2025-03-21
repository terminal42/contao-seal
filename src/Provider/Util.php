<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Provider;

use Contao\CoreBundle\Search\Document;
use Contao\StringUtil;
use Symfony\Component\DomCrawler\Crawler;

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
}

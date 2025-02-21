<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Test\Provider;

use Contao\CoreBundle\Search\Document;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Terminal42\ContaoSeal\Provider\Util;

class UtilTest extends TestCase
{
    public function testExtractSearchableContent(): void
    {
        $this->assertSame(
            'This is just some content to test search indexing!',
            Util::extractSearchableContentFromDocument(new Document(
                new Uri('https://www.contao.org'),
                200,
                [],
                file_get_contents(__DIR__.'/../Fixtures/site.html'),
            ),
            ));
    }

    #[DataProvider('trimSearchContext')]
    public function testTrimSearchContext(string $context, int $numberOfContextWords, string $expectedContext, string $preTag = '<em>', string $postTag = '</em>', string $contextEllipsis = '[…]'): void
    {
        $this->assertSame($expectedContext, Util::trimSearchContext($context, $numberOfContextWords, $contextEllipsis, $preTag, $postTag));
    }

    public static function trimSearchContext(): \Generator
    {
        yield 'Basic example' => [
            'Lorem ipsum dolor sit amet, <em>consectetur</em>[…]adipiscing elit. Etiam eleifend, augue in dictum lacinia, nisi lacus mollis <em>massa</em>, a pulvinar felis dui nec nisl. Pellentesque justo erat, sollicitudin ac dolor finibus, dapibus lacinia diam.',
            20,
            '[…]ipsum dolor sit amet, <em>consectetur</em>[…]adipiscing elit.[…]<em>massa</em>, a pulvinar felis dui[…]',
        ];

        yield 'Context overlapping the end of a sentence' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            30,
            '[…]brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
        ];

        yield 'Context overlapping the start of a sentence' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            40,
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile and thus this sentence went[…]',
        ];

        yield 'Test with different tags' => [
            'The quick brown fox jumps over the lazy <strong>dog</strong>. The <strong>fox</strong> was very agile.',
            30,
            '[…]brown fox jumps over the lazy <strong>dog</strong>. The <strong>fox</strong> was very agile.',
            '<strong>',
            '</strong>',
        ];

        yield 'Test with multiple word matches tags' => [
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            30,
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this[…]',
        ];

        yield 'Test with non-matching highlight tags just leaves the content untouched' => [
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            30,
            'The quick brown fox jumps over the lazy <em>dog</em>. The <em>fox</em> was very agile.',
            '<strong>',
            '</strong>',
        ];

        yield 'Test with different ellipsis' => [
            'The quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and thus this sentence went on forever.',
            20,
            '~~~quick brown fox jumps <em>over the lazy dog</em>. The <em>fox</em> was very agile and~~~',
            '<em>',
            '</em>',
            '~~~',
        ];
    }
}

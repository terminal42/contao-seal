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
    public static function extractSearchableContentProvider(): \Generator
    {
        yield 'Protected elements not allowed' => [
            false,
            'This is just some content to test search indexing!',
        ];

        yield 'Protected elements allowed' => [
            true,
            'This is just some content to test search indexing! This is protected content!',
        ];
    }

    #[DataProvider('extractSearchableContentProvider')]
    public function testExtractSearchableContent(bool $allowProtected, string $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            Util::extractSearchableContentFromDocument(new Document(
                new Uri('https://www.contao.org'),
                200,
                [],
                file_get_contents(__DIR__.'/../Fixtures/site.html'),
            ), $allowProtected));
    }
}

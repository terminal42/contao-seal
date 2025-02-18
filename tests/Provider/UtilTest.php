<?php

declare(strict_types=1);

namespace Terminal42\ContaoSeal\Test\Provider;

use Contao\CoreBundle\Search\Document;
use Nyholm\Psr7\Uri;
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
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Header;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Core\Http\Header\Accept;

final class AcceptTest extends Unit
{
    public function testQualityFromTheMostSpecificRange(): void
    {
        // RFC 9110 §12.5.1 example
        $accept = Accept::parse('text/*;q=0.3, text/plain;q=0.7, text/plain;format=flowed, text/plain;format=fixed;q=0.4, */*;q=0.5');

        $this->assertSame(1.0, $accept->getQuality('text/plain;format=flowed'));
        $this->assertSame(0.7, $accept->getQuality('text/plain'));
        $this->assertSame(0.3, $accept->getQuality('text/html'));
        $this->assertSame(0.5, $accept->getQuality('image/jpeg'));
        $this->assertSame(0.4, $accept->getQuality('text/plain;format=fixed'));
    }

    /**
     * @return iterable<string, array{?string, list<string>, ?string}>
     */
    public static function negotiateProvider(): iterable
    {
        $available = ['application/json', 'application/xml', 'text/html'];
        yield 'no header: server preference' => [null, $available, 'application/json'];
        yield 'empty header' => ['', $available, 'application/json'];
        yield 'exact' => ['text/html', $available, 'text/html'];
        yield 'quality' => ['application/json;q=0.5, application/xml', $available, 'application/xml'];
        yield 'tie: server order' => ['application/xml, application/json', $available, 'application/json'];
        yield 'wildcard subtype' => ['text/*', $available, 'text/html'];
        yield 'browser' => ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', $available, 'text/html'];
        yield 'q=0 excludes' => ['*/*, application/json;q=0', $available, 'application/xml'];
        yield 'nothing acceptable' => ['image/png', $available, null];
        yield 'invalid elements ignored' => ['nonsense, application/xml;q=2, text/html;q=0.1', $available, 'text/html'];
        yield 'case-insensitive' => ['APPLICATION/XML', $available, 'application/xml'];
    }

    /**
     * @param list<string> $available
     */
    #[DataProvider('negotiateProvider')]
    public function testNegotiate(?string $header, array $available, ?string $expected): void
    {
        $this->assertSame($expected, Accept::parse($header)->negotiate($available));
    }

    public function testAccepts(): void
    {
        $this->assertTrue(Accept::parse('application/*')->accepts('application/json'));
        $this->assertFalse(Accept::parse('text/html')->accepts('application/json'));
        $this->assertTrue(Accept::parse(null)->accepts('anything/else'));
    }
}

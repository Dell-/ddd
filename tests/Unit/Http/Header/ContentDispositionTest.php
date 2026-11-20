<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Header;

use Codeception\Test\Unit;
use Core\Http\Header\ContentDisposition;
use Core\Http\Header\DispositionType;

final class ContentDispositionTest extends Unit
{
    public function testAsciiFilename(): void
    {
        $this->assertSame('attachment; filename=report.pdf', (string) ContentDisposition::attachment('report.pdf'));
        $this->assertSame('inline; filename="my report.pdf"', (string) ContentDisposition::inline('my report.pdf'));
        $this->assertSame('attachment', (string) ContentDisposition::attachment());
    }

    public function testUtf8FilenameHasAnAsciiFallback(): void
    {
        $this->assertSame(
            "attachment; filename=\"____ 2026.pdf\"; filename*=UTF-8''%D0%97%D0%B2%D1%96%D1%82%202026.pdf",
            (string) ContentDisposition::attachment('Звіт 2026.pdf')
        );
    }

    public function testPathsAreRemoved(): void
    {
        $this->assertSame('passwd', ContentDisposition::attachment('../../etc/passwd')->filename);
        $this->assertSame('evil.exe', ContentDisposition::parse('attachment; filename="C:\\Windows\\evil.exe"')?->filename);
        $this->assertSame('', ContentDisposition::sanitizeFilename('..'));
        $this->assertSame('ab', ContentDisposition::sanitizeFilename("a\x00\nb"));
    }

    public function testParse(): void
    {
        $disposition = ContentDisposition::parse('form-data; name="photos[]"; filename="a.png"');

        $this->assertSame('form-data', $disposition?->type);
        $this->assertTrue($disposition?->is(DispositionType::FormData));
        $this->assertSame('photos[]', $disposition?->name);
        $this->assertSame('a.png', $disposition?->filename);
        $this->assertNull(ContentDisposition::parse('a b; x=1'));
    }

    public function testExtValueWins(): void
    {
        $this->assertSame('€ rates.txt', ContentDisposition::parse("attachment; filename=\"EURO rates.txt\"; filename*=utf-8''%e2%82%ac%20rates.txt")?->filename);
        $this->assertSame('£ rates.txt', ContentDisposition::parse("attachment; filename*=iso-8859-1'en'%A3%20rates.txt")?->filename);
        $this->assertSame('fallback.txt', ContentDisposition::parse("attachment; filename=fallback.txt; filename*=koi8-r''%C1")?->filename);
    }

    public function testRoundTrip(): void
    {
        $header = (string) ContentDisposition::attachment('Привіт "світ".txt');

        $this->assertSame('Привіт "світ".txt', ContentDisposition::parse($header)?->filename);
    }
}

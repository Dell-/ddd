<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Header;

use Codeception\Test\Unit;
use Core\Http\Header\MediaType;

final class MediaTypeTest extends Unit
{
    public function testParse(): void
    {
        $type = MediaType::fromString('Application/VND.API+JSON; Charset="UTF-8"; profile=x');

        $this->assertSame('application', $type->type);
        $this->assertSame('vnd.api+json', $type->subtype);
        $this->assertSame('application/vnd.api+json', $type->essence);
        $this->assertSame('json', $type->suffix);
        $this->assertSame('utf-8', $type->charset);
        $this->assertSame('x', $type->parameter('PROFILE'));
        $this->assertSame(['charset' => 'UTF-8', 'profile' => 'x'], $type->parameters);
        $this->assertSame('application/vnd.api+json; charset=UTF-8; profile=x', (string) $type);
    }

    public function testInvalid(): void
    {
        foreach (['', 'text', 'text/', '/html', '*/html', 'te xt/html', 'text/html/x'] as $value) {
            $this->assertNull(MediaType::parse($value), $value);
        }
        $this->expectException(\InvalidArgumentException::class);
        MediaType::fromString('nope');
    }

    public function testFamilies(): void
    {
        $this->assertTrue(MediaType::fromString('application/json')->isJson());
        $this->assertTrue(MediaType::fromString('application/merge-patch+json')->isJson());
        $this->assertFalse(MediaType::fromString('application/jsonl')->isJson());
        $this->assertTrue(MediaType::fromString('text/xml')->isXml());
        $this->assertTrue(MediaType::fromString('application/atom+xml')->isXml());
        $this->assertFalse(MediaType::fromString('text/html')->isXml());
    }

    public function testRanges(): void
    {
        $html = MediaType::fromString('text/html');

        $this->assertTrue($html->matches('*/*'));
        $this->assertTrue($html->matches('text/*'));
        $this->assertTrue($html->matches('TEXT/HTML'));
        $this->assertFalse($html->matches('text/plain'));
        $this->assertFalse($html->matches('image/*'));
        $this->assertTrue(MediaType::fromString('text/*')->isRange);
        $this->assertFalse($html->isRange);
    }

    public function testWithParameter(): void
    {
        $type = MediaType::fromString('text/plain')->withParameter('Charset', 'utf-8');

        $this->assertSame('text/plain; charset=utf-8', (string) $type);
        $this->assertSame('text/plain', (string) $type->withoutParameters());
    }
}

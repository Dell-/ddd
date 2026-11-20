<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\Uri;

final class UriTest extends Unit
{
    public function testComponents(): void
    {
        $uri = new Uri('HTTPS://user:pa%20ss@Example.COM:8443/a b/c?x=1 2#frag ment');

        $this->assertSame('https', $uri->getScheme());
        $this->assertSame('user:pa%20ss', $uri->getUserInfo());
        $this->assertSame('example.com', $uri->getHost());
        $this->assertSame(8443, $uri->getPort());
        $this->assertSame('user:pa%20ss@example.com:8443', $uri->getAuthority());
        $this->assertSame('/a%20b/c', $uri->getPath());
        $this->assertSame('x=1%202', $uri->getQuery());
        $this->assertSame('frag%20ment', $uri->getFragment());
        $this->assertSame('https://user:pa%20ss@example.com:8443/a%20b/c?x=1%202#frag%20ment', (string) $uri);
    }

    public function testDefaultPortIsOmitted(): void
    {
        $this->assertNull(new Uri('http://example.com:80/')->getPort());
        $this->assertNull(new Uri('https://example.com:443')->getPort());
        $this->assertSame('http://example.com', (string) new Uri('https://example.com:443')->withScheme('http')->withPort(80));
        $this->assertSame(443, new Uri('http://example.com:443')->getPort());
    }

    public function testExistingEncodingIsKept(): void
    {
        $this->assertSame('/a%2Fb%20c', new Uri('/a%2Fb%20c')->getPath());
        $this->assertSame('/100%25', new Uri()->withPath('/100%')->getPath());
    }

    public function testWithers(): void
    {
        $uri = new Uri()->withScheme('http')->withHost('example.com')->withPath('/items')->withQuery('?a=1')->withFragment('#top');

        $this->assertSame('http://example.com/items?a=1#top', (string) $uri);
        $this->assertSame('http://u:p@example.com/items?a=1#top', (string) $uri->withUserInfo('u', 'p'));
        $this->assertSame('/items', (string) new Uri('/items'));
    }

    public function testPathNormalizationOnOutput(): void
    {
        $this->assertSame('http://example.com/items', (string) new Uri()->withScheme('http')->withHost('example.com')->withPath('items'));
        $this->assertSame('/items', (string) new Uri()->withPath('//items'));
    }

    public function testInvalidPort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Uri()->withPort(70000);
    }
}

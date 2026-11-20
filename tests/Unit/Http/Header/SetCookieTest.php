<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Header;

use Codeception\Test\Unit;
use Core\Http\Header\SameSite;
use Core\Http\Header\SetCookie;

final class SetCookieTest extends Unit
{
    public function testSecureDefaults(): void
    {
        $this->assertSame('id=a%20b%3B; Path=/; HttpOnly; SameSite=Lax', (string) new SetCookie('id', 'a b;'));
    }

    public function testAllAttributes(): void
    {
        $cookie = new SetCookie('session', 'x')
            ->withExpires(new \DateTimeImmutable('2030-01-02 03:04:05 UTC'))
            ->withMaxAge(3600)
            ->withDomain('.Example.COM')
            ->withPath('/app')
            ->withSecure()
            ->withSameSite(SameSite::Strict);

        $this->assertSame(
            'session=x; Expires=Wed, 02 Jan 2030 03:04:05 GMT; Max-Age=3600; Domain=example.com; Path=/app; Secure; HttpOnly; SameSite=Strict',
            (string) $cookie
        );
        $this->assertSame('example.com', $cookie->domain);
    }

    public function testSameSiteNoneAndPartitionedRequireSecure(): void
    {
        $this->assertSame('a=1; Path=/; Secure; SameSite=None', (string) new SetCookie('a', '1')->withHttpOnly(false)->withSameSite(SameSite::None));
        $this->assertSame('a=1; Path=/; Secure; HttpOnly; SameSite=Lax; Partitioned', (string) new SetCookie('a', '1')->withPartitioned());
    }

    public function testExpire(): void
    {
        $this->assertSame('a=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Max-Age=0; Path=/; HttpOnly; SameSite=Lax', (string) SetCookie::expire('a'));
    }

    public function testImmutability(): void
    {
        $cookie = new SetCookie('a');
        $cookie->withSecure()->withValue('x');

        $this->assertFalse($cookie->secure);
        $this->assertSame('', $cookie->value);
    }

    public function testValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SetCookie('bad name');
    }

    public function testPathValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new SetCookie('a')->withPath('/a;Domain=evil');
    }
}

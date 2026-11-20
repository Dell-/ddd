<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Core\Http\Message\Stream;
use Core\Http\Message\Uri;
use Core\Http\Status;

final class MessageTest extends Unit
{
    public function testHeadersAreCaseInsensitive(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/plain', 'X-List' => ['a', 'b']]);

        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertSame(['text/plain'], $response->getHeader('CONTENT-TYPE'));
        $this->assertSame('a, b', $response->getHeaderLine('x-list'));
        $this->assertSame([], $response->getHeader('missing'));

        $changed = $response->withHeader('content-type', 'application/json')->withAddedHeader('X-LIST', 'c')->withoutHeader('x-missing');
        $this->assertSame(['content-type' => ['application/json'], 'X-List' => ['a', 'b', 'c']], $changed->getHeaders());
        $this->assertSame(['Content-Type' => ['text/plain'], 'X-List' => ['a', 'b']], $response->getHeaders(), 'immutable');
    }

    public function testHeaderValidation(): void
    {
        foreach ([['Bad Name', 'x'], ['X-A', "a\r\nInjected: 1"], ['X-A', []], ['X-A', ['ok', "\0"]]] as [$name, $value]) {
            try {
                new Response()->withHeader($name, $value);
                $this->fail('must be rejected: ' . json_encode([$name, $value]));
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(['1'], new Response()->withHeader('X-Number', 1)->getHeader('X-Number'));
        $this->assertSame(['trimmed'], new Response()->withHeader('X-A', "  trimmed\t")->getHeader('X-A'));
    }

    public function testResponse(): void
    {
        $response = new Response(Status::Created, [], 'body');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Created', $response->getReasonPhrase());
        $this->assertSame('body', (string) $response->getBody());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame(['I\'m late', 499], [$response->withStatus(499, 'I\'m late')->getReasonPhrase(), $response->withStatus(499)->getStatusCode()]);
        $this->assertSame('', $response->withStatus(499)->getReasonPhrase());
        $this->assertSame('Not Found', $response->withStatus(Status::NotFound)->getReasonPhrase());
        $this->assertSame('2', $response->withProtocolVersion('2')->getProtocolVersion());
        $this->assertSame('new', (string) $response->withBody(Stream::create('new'))->getBody());
        $this->assertSame('', (string) new Response()->getBody());
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Response(600);
    }

    public function testRequest(): void
    {
        $request = new Request('get', 'https://example.com:8443/items?page=2');

        $this->assertSame('get', $request->getMethod(), 'methods are case-sensitive and kept');
        $this->assertSame('/items?page=2', $request->getRequestTarget());
        $this->assertSame(['example.com:8443'], $request->getHeader('Host'));
        $this->assertSame('Host', array_key_first($request->getHeaders()), 'Host first');
        $this->assertSame('*', $request->withRequestTarget('*')->getRequestTarget());
        $this->assertSame('/', new Request('GET', 'https://example.com')->getRequestTarget());
    }

    public function testWithUriUpdatesHost(): void
    {
        $request = new Request('GET', 'http://a.example/');

        $this->assertSame(['b.example'], $request->withUri(new Uri('http://b.example/'))->getHeader('Host'));
        $this->assertSame(['a.example'], $request->withUri(new Uri('http://b.example/'), true)->getHeader('Host'));
        $this->assertSame(['a.example'], $request->withUri(new Uri('/path'))->getHeader('Host'), 'no host in the URI');
    }

    public function testInvalidMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request('BAD METHOD', '/');
    }
}

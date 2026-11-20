<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Codeception\Test\Unit;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Message\UploadedFile;
use Core\Http\Middleware\BodyParsingMiddleware;
use Core\Http\Middleware\BodySizeLimitMiddleware;
use Core\Http\Middleware\MethodOverrideMiddleware;
use Core\Http\Method;
use Tests\Support\CallableHandler;
use Tests\Support\Requests;

final class BodyMiddlewareTest extends Unit
{
    public function testParsesByContentType(): void
    {
        foreach ([
            ['application/json', '{"a":1}', ['a' => 1]],
            ['application/vnd.api+json', '{"a":1}', ['a' => 1]],
            ['application/x-ndjson', "1\n2\n", [1, 2]],
            ['application/json-seq', "\x1E1\n", [1]],
            ['application/x-www-form-urlencoded', 'a.b=1', ['a.b' => '1']],
        ] as [$type, $body, $expected]) {
            $handler = new CallableHandler();
            new BodyParsingMiddleware()->process(Requests::make('PATCH', '/', ['Content-Type' => $type], $body), $handler);

            $this->assertSame($expected, $handler->request?->getParsedBody(), $type);
        }
    }

    public function testMultipartForPut(): void
    {
        $body = "--b\r\nContent-Disposition: form-data; name=\"title\"\r\n\r\nT\r\n--b\r\nContent-Disposition: form-data; name=\"f\"; filename=\"a.txt\"\r\n\r\nabc\r\n--b--\r\n";
        $handler = new CallableHandler();
        new BodyParsingMiddleware()->process(Requests::make('PUT', '/', ['Content-Type' => 'multipart/form-data; boundary=b'], $body), $handler);

        $this->assertSame(['title' => 'T'], $handler->request?->getParsedBody());
        $file = $handler->request?->getUploadedFiles()['f'] ?? null;
        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertSame('abc', $file->getContents());
    }

    public function testUnknownTypesAndPhpParsedBodiesAreKept(): void
    {
        $handler = new CallableHandler();
        new BodyParsingMiddleware()->process(Requests::make('PUT', '/', ['Content-Type' => 'image/png'], 'PNG'), $handler);
        $this->assertNull($handler->request?->getParsedBody());
        $this->assertSame('PNG', (string) $handler->request?->getBody());

        new BodyParsingMiddleware()->process(Requests::make('POST', '/', ['Content-Type' => 'application/json'], '{"a":1}')->withParsedBody(['php' => true]), $handler);
        $this->assertSame(['php' => true], $handler->request?->getParsedBody());
    }

    public function testBodySizeLimit(): void
    {
        $limit = new BodySizeLimitMiddleware(5);

        $this->assertSame(200, $limit->process(Requests::make('POST', '/', ['Content-Length' => '5'], '12345'), new CallableHandler())->getStatusCode());
        try {
            $limit->process(Requests::make('POST', '/', ['Content-Length' => '6'], '123456'), new CallableHandler());
            $this->fail('413 expected');
        } catch (ContentTooLargeException $e) {
            $this->assertSame(413, $e->getStatusCode());
        }
        $this->expectException(ContentTooLargeException::class);
        $limit->process(Requests::make('POST', '/', [], '123456'), new CallableHandler()); // chunked: no Content-Length
    }

    public function testMethodOverride(): void
    {
        $override = new MethodOverrideMiddleware();
        $handler = new CallableHandler();

        $override->process(Requests::make('POST', '/', ['X-HTTP-Method-Override' => 'patch']), $handler);
        $this->assertSame('PATCH', $handler->request?->getMethod());

        $override->process(Requests::make('POST')->withParsedBody(['_method' => 'DELETE', 'id' => '1']), $handler);
        $this->assertSame('DELETE', $handler->request?->getMethod());
        $this->assertSame(['id' => '1'], $handler->request?->getParsedBody(), '_method removed');

        $override->process(Requests::make('POST', '/', ['X-HTTP-Method-Override' => 'GET']), $handler);
        $this->assertSame('POST', $handler->request?->getMethod(), 'never to a safe method');

        $override->process(Requests::make('GET', '/', ['X-HTTP-Method-Override' => 'DELETE']), $handler);
        $this->assertSame('GET', $handler->request?->getMethod(), 'only POST');

        new MethodOverrideMiddleware([Method::Delete])->process(Requests::make('POST', '/', ['X-HTTP-Method-Override' => 'PUT']), $handler);
        $this->assertSame('POST', $handler->request?->getMethod(), 'configurable');
    }
}

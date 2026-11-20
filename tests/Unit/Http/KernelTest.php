<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Codeception\Test\Unit;
use Core\Http\Emitter;
use Core\Http\Kernel;
use Core\Http\Message\Response;
use Core\Http\Responder;
use Core\Http\View\View;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tests\Support\CallableHandler;
use Tests\Support\Requests;
use Tests\Support\TestApplication;

final class KernelTest extends Unit
{
    public function testMiddlewareOrder(): void
    {
        $log = new \ArrayObject();
        $middleware = static fn (string $name): MiddlewareInterface => new class ($name, $log) implements MiddlewareInterface {
            public function __construct(private readonly string $name, private readonly \ArrayObject $log)
            {
            }

            #[\Override]
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $this->log[] = 'before ' . $this->name;
                $response = $handler->handle($request->withAttribute('trace', $request->getAttribute('trace', '') . $this->name));
                $this->log[] = 'after ' . $this->name;

                return $response->withAddedHeader('X-Trace', $this->name);
            }
        };
        $handler = new CallableHandler(static fn (ServerRequestInterface $request) => new Response(200, [], (string) $request->getAttribute('trace')));

        $response = new Kernel($handler, [$middleware('a'), $middleware('b')])->handle(Requests::make());

        $this->assertSame('ab', (string) $response->getBody());
        $this->assertSame(['before a', 'before b', 'after b', 'after a'], $log->getArrayCopy());
        $this->assertSame('b, a', $response->getHeaderLine('X-Trace'));
        $this->assertSame('ok', (string) new Kernel(new CallableHandler())->handle(Requests::make())->getBody(), 'no middleware');
    }

    public function testConfiguredPipeline(): void
    {
        $kernel = TestApplication::createContainer()->get(Kernel::class);
        \assert($kernel instanceof Kernel);

        $response = $kernel->handle(Requests::make('GET', '/nope', ['Accept' => 'application/json']));
        $this->assertSame(['application/problem+json', 'nosniff'], [$response->getHeaderLine('Content-Type'), $response->getHeaderLine('X-Content-Type-Options')], 'security headers on errors');

        $cors = $kernel->handle(Requests::make('GET', 'http://localhost/nope', ['Origin' => 'https://app.example.com']));
        $this->assertSame([404, 'https://app.example.com'], [$cors->getStatusCode(), $cors->getHeaderLine('Access-Control-Allow-Origin')], 'CORS headers on errors');

        $created = $kernel->handle(Requests::make('POST', '/items', ['Content-Type' => 'application/json'], '{"name":"N"}'));
        $this->assertSame([201, '/items/42'], [$created->getStatusCode(), $created->getHeaderLine('Location')]);
    }

    public function testResponder(): void
    {
        $responder = new Responder();
        $request = Requests::make('GET', '/', ['Accept' => 'application/json']);
        $response = new Response(418);

        $this->assertSame($response, $responder->respond($response, $request));
        $this->assertSame(204, $responder->respond(null, $request)->getStatusCode());
        $this->assertSame('text/html; charset=utf-8', $responder->respond('<p>', $request)->getHeaderLine('Content-Type'));
        $this->assertSame('[1,2]', (string) $responder->respond([1, 2], $request)->getBody());
        $this->assertSame('{"a":1}', (string) $responder->respond(new View(['a' => 1]), $request)->getBody());
        $this->assertSame('true', (string) $responder->respond(true, $request)->getBody());
        $this->assertSame('{"x":1}', (string) $responder->respond(new class implements \JsonSerializable, \Stringable {
            public function jsonSerialize(): array { return ['x' => 1]; }
            public function __toString(): string { return 'string'; }
        }, $request)->getBody(), 'JsonSerializable wins over Stringable');
    }

    public function testEmitterBody(): void
    {
        $emit = static function (ResponseInterface $response, bool $withBody = true): string {
            ob_start();
            new Emitter(4)->emit($response, $withBody);

            return (string) ob_get_clean();
        };

        $this->assertSame('chunked body', $emit(new Response(200, [], 'chunked body')));
        $this->assertSame('', $emit(new Response(200, [], 'x'), false), 'HEAD');
        $this->assertSame('', $emit(new Response(204, [], 'x')));
        $this->assertSame('', $emit(new Response(304, [], 'x')));
        $this->assertSame('', $emit(new Response(101, [], 'x')));
    }
}

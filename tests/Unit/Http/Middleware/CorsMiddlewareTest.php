<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Codeception\Test\Unit;
use Core\Http\Message\Response;
use Core\Http\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\CallableHandler;
use Tests\Support\Requests;

final class CorsMiddlewareTest extends Unit
{
    private CorsMiddleware $cors;

    protected function _before(): void
    {
        $this->cors = new CorsMiddleware(
            'https://app.example.com, #^https://[a-z]+\.partner\.example$#',
            exposedHeaders: ['Location', 'X-Total'],
            allowCredentials: true,
            maxAge: 3600
        );
    }

    public function testNotCorsRequests(): void
    {
        $handler = new CallableHandler();

        $this->assertFalse($this->process('GET', [], $handler)->hasHeader('Access-Control-Allow-Origin'));
        $this->assertFalse($this->process('POST', ['Origin' => 'http://localhost'], $handler)->hasHeader('Vary'), 'same origin');
    }

    public function testActualRequest(): void
    {
        $response = $this->process('GET', ['Origin' => 'https://app.example.com']);

        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertSame('Location, X-Total', $response->getHeaderLine('Access-Control-Expose-Headers'));
        $this->assertSame('Origin', $response->getHeaderLine('Vary'));
        $this->assertSame('https://shop.partner.example', $this->process('GET', ['Origin' => 'https://shop.partner.example'])->getHeaderLine('Access-Control-Allow-Origin'), 'pattern');
    }

    public function testDisallowedOriginGetsNoCorsHeaders(): void
    {
        $response = $this->process('GET', ['Origin' => 'https://evil.example']);

        $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $response->getHeaderLine('Vary'), 'still varies');
        $this->assertSame('ok', (string) $response->getBody(), 'the server cannot block it, the browser does');
    }

    public function testPreflight(): void
    {
        $handler = new CallableHandler();
        $response = $this->process('OPTIONS', [
            'Origin' => 'https://app.example.com',
            'Access-Control-Request-Method' => 'PUT',
            'Access-Control-Request-Headers' => 'Content-Type, X-CSRF-Token',
        ], $handler);

        $this->assertNull($handler->request, 'answered by the middleware');
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('https://app.example.com', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertSame('GET, HEAD, POST, PUT, PATCH, DELETE', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertSame('content-type, x-csrf-token', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame('true', $response->getHeaderLine('Access-Control-Allow-Credentials'));
        $this->assertSame('3600', $response->getHeaderLine('Access-Control-Max-Age'));
        $this->assertStringContainsString('Origin', $response->getHeaderLine('Vary'));
    }

    public function testPreflightRejections(): void
    {
        foreach ([
            ['Origin' => 'https://evil.example', 'Access-Control-Request-Method' => 'GET'],
            ['Origin' => 'https://app.example.com', 'Access-Control-Request-Method' => 'TRACE'],
            ['Origin' => 'https://app.example.com', 'Access-Control-Request-Method' => 'GET', 'Access-Control-Request-Headers' => 'X-Secret'],
        ] as $headers) {
            $response = $this->process('OPTIONS', $headers);
            $this->assertSame(403, $response->getStatusCode(), json_encode($headers));
            $this->assertFalse($response->hasHeader('Access-Control-Allow-Origin'));
            $this->assertNotSame('', (string) $response->getBody(), 'reason for developers');
        }
    }

    public function testSafelistedHeadersNeedNoPermission(): void
    {
        $response = $this->process('OPTIONS', ['Origin' => 'https://app.example.com', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'accept, content-language']);

        $this->assertSame(204, $response->getStatusCode());
    }

    public function testWildcard(): void
    {
        $cors = new CorsMiddleware(['*'], allowedHeaders: ['*']);
        $request = Requests::make('OPTIONS', '/', ['Origin' => 'https://any.example', 'Access-Control-Request-Method' => 'GET', 'Access-Control-Request-Headers' => 'X-Anything']);

        $response = $cors->process($request, new CallableHandler());
        $this->assertSame('*', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertFalse($response->hasHeader('Access-Control-Allow-Credentials'));

        // with credentials "*" is not allowed: the origin is echoed (Fetch §3.2.5)
        $withCredentials = new CorsMiddleware(['*'], allowCredentials: true)->process(Requests::make('GET', '/', ['Origin' => 'https://any.example']), new CallableHandler());
        $this->assertSame('https://any.example', $withCredentials->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testVaryIsMerged(): void
    {
        $handler = new CallableHandler(static fn () => new Response(200, ['Vary' => 'Accept']));

        $this->assertSame('Accept, Origin', $this->process('GET', ['Origin' => 'https://app.example.com'], $handler)->getHeaderLine('Vary'));
    }

    /**
     * @param array<string, string> $headers
     */
    private function process(string $method, array $headers, CallableHandler $handler = new CallableHandler()): ResponseInterface
    {
        return $this->cors->process(Requests::make($method, 'http://localhost/api', $headers), $handler);
    }
}

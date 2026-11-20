<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Codeception\Test\Unit;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotFoundException;
use Core\Http\Message\Response;
use Core\Http\Middleware\ErrorHandlerMiddleware;
use Core\Http\Middleware\SecurityHeadersMiddleware;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Tests\Support\CallableHandler;
use Tests\Support\Requests;

final class ErrorAndSecurityTest extends Unit
{
    public function testHttpErrorBecomesProblemDetails(): void
    {
        $log = new TestHandler();
        $middleware = new ErrorHandlerMiddleware(new Logger('t', [$log]));

        $response = $middleware->process(
            Requests::make('DELETE', '/', ['Accept' => 'application/json']),
            new CallableHandler(static fn () => throw new MethodNotAllowedException(['GET'], 'No DELETE'))
        );

        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('application/problem+json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('GET', $response->getHeaderLine('Allow'));
        $this->assertSame('{"type":"about:blank","title":"Method Not Allowed","status":405,"detail":"No DELETE"}', (string) $response->getBody());
        $this->assertTrue($log->hasNoticeThatContains('No DELETE'));
    }

    public function testOtherErrorsAre500WithoutDetails(): void
    {
        $log = new TestHandler();
        $handler = new CallableHandler(static fn () => throw new \RuntimeException('database password is x'));

        $response = new ErrorHandlerMiddleware(new Logger('t', [$log]))->process(Requests::make(), $handler);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringNotContainsString('password', (string) $response->getBody());
        $this->assertTrue($log->hasErrorThatContains('database password'));

        $debug = new ErrorHandlerMiddleware(new Logger('t', [$log]), debug: true)->process(Requests::make(), $handler);
        $this->assertStringContainsString('RuntimeException: database password is x', (string) $debug->getBody());
    }

    public function testSuccessIsPassedThrough(): void
    {
        $response = new ErrorHandlerMiddleware(new Logger('t'))->process(Requests::make(), new CallableHandler());

        $this->assertSame('ok', (string) $response->getBody());
        $this->assertSame(404, new ErrorHandlerMiddleware(new Logger('t'))->process(Requests::make(), new CallableHandler(static fn () => throw new NotFoundException()))->getStatusCode());
    }

    public function testSecurityHeaders(): void
    {
        $response = new SecurityHeadersMiddleware()->process(Requests::make(), new CallableHandler());

        $this->assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        $this->assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
        $this->assertSame("frame-ancestors 'none'", $response->getHeaderLine('Content-Security-Policy'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeaderLine('Referrer-Policy'));
        $this->assertFalse($response->hasHeader('Strict-Transport-Security'), 'only on https');
    }

    public function testHstsOnHttpsAndOverrides(): void
    {
        $middleware = new SecurityHeadersMiddleware(['X-Frame-Options' => '', 'Permissions-Policy' => 'camera=()']);
        $handler = new CallableHandler(static fn () => new Response(200, ['Content-Security-Policy' => "default-src 'self'"]));

        $response = $middleware->process(Requests::make('GET', 'https://example.com/'), $handler);

        $this->assertSame('max-age=31536000; includeSubDomains', $response->getHeaderLine('Strict-Transport-Security'));
        $this->assertFalse($response->hasHeader('X-Frame-Options'), 'removed default');
        $this->assertSame('camera=()', $response->getHeaderLine('Permissions-Policy'));
        $this->assertSame("default-src 'self'", $response->getHeaderLine('Content-Security-Policy'), 'action header kept');
    }
}

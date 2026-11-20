<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Codeception\Test\Unit;
use Core\Http\Exception\ForbiddenException;
use Core\Http\Middleware\CsrfMiddleware;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\CallableHandler;
use Tests\Support\Requests;

final class CsrfMiddlewareTest extends Unit
{
    private const string SECRET = 'test-secret-0123456789abcdef0123456789';

    private CsrfMiddleware $csrf;

    protected function _before(): void
    {
        $this->csrf = new CsrfMiddleware(self::SECRET, except: ['/webhooks/'], trustedOrigins: ['https://admin.example.com']);
    }

    public function testSafeRequestGetsAToken(): void
    {
        $handler = new CallableHandler();
        $response = $this->csrf->process(Requests::make('GET', 'https://example.com/'), $handler);

        $this->assertMatchesRegularExpression('/^XSRF-TOKEN=[\w-]+\.[\w-]+; Path=\/; Secure; SameSite=Lax$/', $response->getHeaderLine('Set-Cookie'));
        $token = $handler->request?->getAttribute(CsrfMiddleware::ATTRIBUTE);
        $this->assertIsString($token);
        $this->assertTrue($this->csrf->isValid($token));
        $this->assertStringNotContainsString('HttpOnly', $response->getHeaderLine('Set-Cookie'), 'scripts must read it');
    }

    public function testValidCookieIsKept(): void
    {
        $token = $this->csrf->generateToken();
        $response = $this->csrf->process(Requests::make('GET', '/', cookies: ['XSRF-TOKEN' => $token]), new CallableHandler());

        $this->assertFalse($response->hasHeader('Set-Cookie'));
    }

    public function testUnsafeRequestWithTheToken(): void
    {
        $token = $this->csrf->generateToken();

        foreach (['X-XSRF-TOKEN', 'X-CSRF-Token'] as $header) {
            $this->assertSame(200, $this->unsafe([$header => $token], $token)->getStatusCode(), $header);
        }

        $form = Requests::make('POST', '/', ['Origin' => 'http://localhost'], cookies: ['XSRF-TOKEN' => $token])->withParsedBody(['_csrf' => $token]);
        $this->assertSame(200, $this->csrf->process($form, new CallableHandler())->getStatusCode(), 'form field');
    }

    public function testRejections(): void
    {
        $token = $this->csrf->generateToken();
        $other = $this->csrf->generateToken();
        $forged = explode('.', $token)[0] . '.forged';

        foreach ([
            'no token' => [[], $token],
            'other token' => [['X-CSRF-Token' => $other], $token],
            'forged cookie' => [['X-CSRF-Token' => $forged], $forged],
            'no cookie token' => [['X-CSRF-Token' => $token], null],
            'foreign origin' => [['X-CSRF-Token' => $token, 'Origin' => 'https://evil.example'], $token],
            'null origin' => [['X-CSRF-Token' => $token, 'Origin' => 'null'], $token],
            'cross-site' => [['X-CSRF-Token' => $token, 'Sec-Fetch-Site' => 'cross-site'], $token],
        ] as $case => [$headers, $cookie]) {
            try {
                $this->unsafe($headers, $cookie);
                $this->fail('403 expected: ' . $case);
            } catch (ForbiddenException) {
                $this->assertTrue(true);
            }
        }
    }

    public function testTrustedOriginAndSameSite(): void
    {
        $token = $this->csrf->generateToken();

        $this->assertSame(200, $this->unsafe(['X-CSRF-Token' => $token, 'Origin' => 'https://admin.example.com'], $token)->getStatusCode());
        $this->assertSame(200, $this->unsafe(['X-CSRF-Token' => $token, 'Sec-Fetch-Site' => 'same-origin'], $token)->getStatusCode());
    }

    public function testExemptions(): void
    {
        $handler = new CallableHandler();

        $this->assertSame(200, $this->csrf->process(Requests::make('POST', '/api/items'), $handler)->getStatusCode(), 'no cookies: not CSRF-able');
        $this->assertSame(200, $this->csrf->process(Requests::make('POST', '/webhooks/payment', cookies: ['a' => 'b']), $handler)->getStatusCode(), 'excepted path');
        $this->assertSame(200, $this->csrf->process(Requests::make('OPTIONS', '/', cookies: ['a' => 'b']), $handler)->getStatusCode(), 'safe method');

        $this->expectException(ForbiddenException::class);
        new CsrfMiddleware(self::SECRET, skipWithoutCookies: false)->process(Requests::make('POST', '/api/items'), $handler);
    }

    public function testSecretLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CsrfMiddleware('short');
    }

    /**
     * @param array<string, string> $headers
     */
    private function unsafe(array $headers, ?string $cookie): ResponseInterface
    {
        $cookies = $cookie === null ? ['session' => 'x'] : ['XSRF-TOKEN' => $cookie];

        return $this->csrf->process(Requests::make('DELETE', 'http://localhost/items/1', $headers, cookies: $cookies), new CallableHandler());
    }
}

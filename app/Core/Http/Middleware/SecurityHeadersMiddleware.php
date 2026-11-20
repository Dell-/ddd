<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security response headers (OWASP HTTP Headers Cheat Sheet). Headers set by the action are kept.
 *
 * - X-Content-Type-Options: nosniff — no MIME sniffing (Fetch §3.5)
 * - Content-Security-Policy: frame-ancestors 'none' and X-Frame-Options: DENY — no clickjacking
 * - Referrer-Policy: strict-origin-when-cross-origin
 * - Cross-Origin-Opener-Policy: same-origin
 * - Strict-Transport-Security (RFC 6797) only on https
 */
final readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public const array DEFAULT_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Content-Security-Policy' => "frame-ancestors 'none'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ];

    /**
     * @param array<string, string> $headers headers for every response ("" removes a default)
     * @param string|null $hsts Strict-Transport-Security value for https, null to disable
     */
    public function __construct(
        private array $headers = [],
        private ?string $hsts = 'max-age=31536000; includeSubDomains',
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $headers = array_filter($this->headers + self::DEFAULT_HEADERS, static fn (string $value): bool => $value !== '');
        if ($this->hsts !== null && $request->getUri()->getScheme() === 'https') {
            $headers['Strict-Transport-Security'] = $this->hsts;
        }
        foreach ($headers as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}

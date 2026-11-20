<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Header\HeaderValue;
use Core\Http\Message\Response;
use Core\Http\Status;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Cross-Origin Resource Sharing (WHATWG Fetch Standard §3.2 "CORS protocol").
 *
 * - No Origin header or the request's own origin: not a CORS request, passed through.
 * - Preflight (OPTIONS + Origin + Access-Control-Request-Method): answered here with 204 and
 *   Access-Control-Allow-* headers; 403 (without CORS headers) if the origin, method or a header is not allowed.
 *   Put it outside ErrorHandlerMiddleware, so error responses get the CORS headers too (otherwise the
 *   browser hides the error from the script).
 * - Actual CORS request: passed to the application, the response gets Access-Control-Allow-Origin,
 *   -Allow-Credentials and -Expose-Headers if the origin is allowed (otherwise the browser blocks it).
 * Responses carry Vary: Origin, since they depend on it.
 *
 * Origins are exact ("https://app.example.com"), "*" or regular expressions ("#^https://[a-z]+\.example\.com$#").
 * With credentials the origin is echoed instead of "*" (Fetch §3.2.5).
 */
final readonly class CorsMiddleware implements MiddlewareInterface
{
    /** CORS-safelisted request headers (Fetch §2.2.2) do not need to be allowed */
    private const array SAFELISTED_HEADERS = ['accept', 'accept-language', 'content-language', 'content-type', 'range'];

    /** @var list<string> */
    private array $allowedOrigins;

    /**
     * @param list<string>|string $allowedOrigins a list or a comma/space separated string (e.g. from an environment variable)
     * @param list<string> $allowedMethods
     * @param list<string> $allowedHeaders ["*"] allows any header (not with credentials, Fetch §3.2.5)
     * @param list<string> $exposedHeaders response headers readable by scripts
     */
    public function __construct(
        array|string $allowedOrigins = [],
        private array $allowedMethods = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'],
        private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token', 'X-XSRF-TOKEN'],
        private array $exposedHeaders = [],
        private bool $allowCredentials = false,
        private ?int $maxAge = 600,
    ) {
        $this->allowedOrigins = is_string($allowedOrigins)
            ? preg_split('/[\s,]+/', $allowedOrigins, -1, PREG_SPLIT_NO_EMPTY) ?: []
            : $allowedOrigins;
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        if ($origin === '' || $origin === $this->ownOrigin($request)) {
            return $handler->handle($request);
        }

        $isPreflight = $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
        if ($isPreflight) {
            return $this->preflight($request, $origin);
        }

        $response = $handler->handle($request);
        $response = $this->addVary($response, 'Origin');
        if (!$this->isOriginAllowed($origin)) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $this->allowOriginValue($origin));
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        if ($this->exposedHeaders !== []) {
            $response = $response->withHeader('Access-Control-Expose-Headers', implode(', ', $this->exposedHeaders));
        }

        return $response;
    }

    private function preflight(ServerRequestInterface $request, string $origin): ResponseInterface
    {
        if (!$this->isOriginAllowed($origin)) {
            return $this->rejectPreflight(sprintf('Origin "%s" is not allowed.', $origin));
        }

        $method = $request->getHeaderLine('Access-Control-Request-Method');
        if (!in_array($method, $this->allowedMethods, true)) {
            return $this->rejectPreflight(sprintf('Method "%s" is not allowed for cross-origin requests.', $method));
        }

        $requested = array_map('strtolower', HeaderValue::splitList($request->getHeaderLine('Access-Control-Request-Headers')));
        $anyHeader = in_array('*', $this->allowedHeaders, true) && !$this->allowCredentials;
        $allowed = array_map('strtolower', $this->allowedHeaders);
        $denied = $anyHeader ? [] : array_diff($requested, $allowed, self::SAFELISTED_HEADERS);
        if ($denied !== []) {
            return $this->rejectPreflight(sprintf('Headers not allowed for cross-origin requests: %s.', implode(', ', $denied)));
        }

        $headers = [
            'Access-Control-Allow-Origin' => $this->allowOriginValue($origin),
            'Access-Control-Allow-Methods' => implode(', ', $this->allowedMethods),
            'Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ];
        if ($requested !== []) {
            $headers['Access-Control-Allow-Headers'] = implode(', ', $requested);
        }
        if ($this->allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }
        if ($this->maxAge !== null) {
            $headers['Access-Control-Max-Age'] = (string) $this->maxAge;
        }

        return new Response(Status::NoContent, $headers);
    }

    /**
     * A failed preflight is any non-2xx response without CORS headers (Fetch §4.8); the reason is for developers.
     */
    private function rejectPreflight(string $reason): ResponseInterface
    {
        return new Response(Status::Forbidden, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ], $reason);
    }

    private function isOriginAllowed(string $origin): bool
    {
        return array_any($this->allowedOrigins, static fn (string $allowed): bool => match (true) {
            $allowed === '*' => true,
            str_starts_with($allowed, '#') => (bool) preg_match($allowed, $origin),
            default => strcasecmp($allowed, $origin) === 0,
        });
    }

    private function allowOriginValue(string $origin): string
    {
        return in_array('*', $this->allowedOrigins, true) && !$this->allowCredentials ? '*' : $origin;
    }

    private function ownOrigin(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();

        return $uri->getScheme() . '://' . $uri->getHost() . ($uri->getPort() !== null ? ':' . $uri->getPort() : '');
    }

    private function addVary(ResponseInterface $response, string $header): ResponseInterface
    {
        $vary = $response->getHeaderLine('Vary');

        return match (true) {
            $vary === '' => $response->withHeader('Vary', $header),
            in_array(strtolower($header), array_map('strtolower', HeaderValue::splitList($vary)), true) => $response,
            default => $response->withHeader('Vary', $vary . ', ' . $header),
        };
    }
}

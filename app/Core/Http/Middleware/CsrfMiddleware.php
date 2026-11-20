<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Exception\ForbiddenException;
use Core\Http\Header\SameSite;
use Core\Http\Header\SetCookie;
use Core\Http\Method;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CSRF protection (OWASP CSRF Prevention Cheat Sheet) without server-side sessions:
 *
 * 1. Fetch Metadata / Origin: an unsafe request whose Sec-Fetch-Site is "cross-site" or whose Origin
 *    is foreign (and not trusted) is rejected.
 * 2. Signed double-submit cookie: the token is random + HMAC-SHA256(secret); it is set in a cookie
 *    readable by scripts (XSRF-TOKEN) and must be sent back in a header (X-XSRF-TOKEN / X-CSRF-Token,
 *    as Angular/axios do) or the "_csrf" form field. An attacker can neither read the cookie nor
 *    forge a signed token.
 *
 * Safe methods (RFC 9110 §9.2.1) are never checked and get a token cookie if they lack one; the token
 * is available to templates as the "csrf_token" request attribute. Requests without any cookie are
 * not checked by default: CSRF abuses ambient credentials, which they do not have (token APIs).
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    public const string ATTRIBUTE = 'csrf_token';

    /**
     * @param list<string> $headerNames
     * @param list<string> $except path prefixes without protection (e.g. webhooks)
     * @param list<string> $trustedOrigins other origins allowed to send unsafe requests
     */
    public function __construct(
        #[\SensitiveParameter] private string $secret,
        private string $cookieName = 'XSRF-TOKEN',
        private array $headerNames = ['X-XSRF-TOKEN', 'X-CSRF-Token'],
        private string $fieldName = '_csrf',
        private array $except = [],
        private array $trustedOrigins = [],
        private bool $skipWithoutCookies = true,
        private SameSite $sameSite = SameSite::Lax,
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('The CSRF secret must have at least 32 characters (APP_SECRET).');
        }
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookieToken = $request->getCookieParams()[$this->cookieName] ?? null;
        $validCookie = is_string($cookieToken) && $this->isValid($cookieToken);
        $token = $validCookie ? $cookieToken : $this->generateToken();

        $method = Method::fromName($request->getMethod());
        $protected = !($method?->isSafe() ?? false)
            && !$this->isExcepted($request->getUri()->getPath())
            && !($this->skipWithoutCookies && $request->getCookieParams() === []);

        if ($protected) {
            $this->checkOrigin($request);
            $submitted = $this->submittedToken($request);
            if (!$validCookie || $submitted === null || !hash_equals($cookieToken, $submitted)) {
                throw new ForbiddenException('Invalid or missing CSRF token.');
            }
        }

        $response = $handler->handle($request->withAttribute(self::ATTRIBUTE, $token));

        if (!$validCookie) {
            $cookie = new SetCookie($this->cookieName, $token)
                ->withHttpOnly(false) // scripts must read it to send it back
                ->withSameSite($this->sameSite)
                ->withSecure($request->getUri()->getScheme() === 'https');
            $response = $response->withAddedHeader('Set-Cookie', (string) $cookie);
        }

        return $response;
    }

    public function generateToken(): string
    {
        $random = self::base64Url(random_bytes(32));

        return $random . '.' . $this->sign($random);
    }

    public function isValid(string $token): bool
    {
        $parts = explode('.', $token);

        return count($parts) === 2 && hash_equals($this->sign($parts[0]), $parts[1]);
    }

    private function sign(string $value): string
    {
        return self::base64Url(hash_hmac('sha256', $value, $this->secret, true));
    }

    private function submittedToken(ServerRequestInterface $request): ?string
    {
        foreach ($this->headerNames as $name) {
            $value = $request->getHeaderLine($name);
            if ($value !== '') {
                return $value;
            }
        }
        $body = $request->getParsedBody();
        $value = is_array($body) ? ($body[$this->fieldName] ?? null) : null;

        return is_string($value) ? $value : null;
    }

    private function checkOrigin(ServerRequestInterface $request): void
    {
        if ($request->getHeaderLine('Sec-Fetch-Site') === 'cross-site') {
            throw new ForbiddenException('Cross-site requests are not allowed.');
        }

        $origin = $request->getHeaderLine('Origin');
        if ($origin === '') {
            return;
        }
        $uri = $request->getUri();
        $own = $uri->getScheme() . '://' . $uri->getHost() . ($uri->getPort() !== null ? ':' . $uri->getPort() : '');
        if (strcasecmp($origin, $own) !== 0 && !in_array($origin, $this->trustedOrigins, true)) {
            throw new ForbiddenException(sprintf('Origin "%s" is not allowed.', $origin));
        }
    }

    private function isExcepted(string $path): bool
    {
        return array_any($this->except, static fn (string $prefix): bool => str_starts_with($path, $prefix));
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}

<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Set-Cookie header value (RFC 6265bis §4.1). Immutable; secure defaults: Path=/, HttpOnly, SameSite=Lax.
 *
 *     $response->withAddedHeader('Set-Cookie', (string) new SetCookie('theme', 'dark')->withMaxAge(86400)->withSecure());
 */
final class SetCookie implements \Stringable
{
    public private(set) ?int $expires = null;

    public private(set) ?int $maxAge = null;

    public private(set) ?string $domain = null;

    public private(set) ?string $path = '/';

    public private(set) bool $secure = false;

    public private(set) bool $httpOnly = true;

    public private(set) ?SameSite $sameSite = SameSite::Lax;

    public private(set) bool $partitioned = false;

    public function __construct(
        public private(set) string $name,
        public private(set) string $value = '',
    ) {
        if (!HeaderValue::isToken($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid cookie name "%s".', $name));
        }
    }

    /**
     * A cookie that deletes the cookie with this name (RFC 6265bis §5.6.2: Max-Age=0).
     */
    public static function expire(string $name, ?string $path = '/', ?string $domain = null): self
    {
        return new self($name)->withMaxAge(0)->withExpires(1)->withPath($path)->withDomain($domain);
    }

    public function withValue(string $value): self
    {
        return $this->with('value', $value);
    }

    public function withExpires(\DateTimeInterface|int|null $expires): self
    {
        return $this->with('expires', $expires instanceof \DateTimeInterface ? $expires->getTimestamp() : $expires);
    }

    public function withMaxAge(?int $seconds): self
    {
        return $this->with('maxAge', $seconds === null ? null : max(0, $seconds));
    }

    public function withDomain(?string $domain): self
    {
        return $this->with('domain', $domain === null || $domain === '' ? null : ltrim(strtolower($domain), '.'));
    }

    public function withPath(?string $path): self
    {
        if ($path !== null && ($path === '' || $path[0] !== '/' || preg_match('/[\x00-\x1F;\x7F]/', $path))) {
            throw new \InvalidArgumentException(sprintf('Invalid cookie path "%s".', $path));
        }

        return $this->with('path', $path);
    }

    public function withSecure(bool $secure = true): self
    {
        return $this->with('secure', $secure);
    }

    public function withHttpOnly(bool $httpOnly = true): self
    {
        return $this->with('httpOnly', $httpOnly);
    }

    public function withSameSite(?SameSite $sameSite): self
    {
        return $this->with('sameSite', $sameSite);
    }

    /**
     * CHIPS: partitioned cookie (requires Secure).
     */
    public function withPartitioned(bool $partitioned = true): self
    {
        return $this->with('partitioned', $partitioned);
    }

    #[\Override]
    public function __toString(): string
    {
        // cookie-octets exclude spaces, quotes, commas, semicolons and backslashes: the value is
        // percent-encoded (PHP decodes it into $_COOKIE)
        $cookie = $this->name . '=' . rawurlencode($this->value);

        if ($this->expires !== null) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s', $this->expires) . ' GMT';
        }
        if ($this->maxAge !== null) {
            $cookie .= '; Max-Age=' . $this->maxAge;
        }
        if ($this->domain !== null) {
            $cookie .= '; Domain=' . $this->domain;
        }
        if ($this->path !== null) {
            $cookie .= '; Path=' . $this->path;
        }
        // browsers reject SameSite=None and Partitioned without Secure
        if ($this->secure || $this->sameSite === SameSite::None || $this->partitioned) {
            $cookie .= '; Secure';
        }
        if ($this->httpOnly) {
            $cookie .= '; HttpOnly';
        }
        if ($this->sameSite !== null) {
            $cookie .= '; SameSite=' . $this->sameSite->value;
        }
        if ($this->partitioned) {
            $cookie .= '; Partitioned';
        }

        return $cookie;
    }

    private function with(string $property, mixed $value): self
    {
        $clone = clone $this;
        $clone->{$property} = $value;

        return $clone;
    }
}

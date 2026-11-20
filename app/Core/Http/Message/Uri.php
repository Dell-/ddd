<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Psr\Http\Message\UriInterface;

/**
 * PSR-7 URI (RFC 3986). Components are stored percent-encoded, scheme and host in lower case,
 * the default port of the scheme is omitted.
 */
final class Uri implements UriInterface, \Stringable
{
    private const array DEFAULT_PORTS = ['http' => 80, 'https' => 443, 'ws' => 80, 'wss' => 443];

    // RFC 3986 §2.2-2.3: characters that are left as they are
    private const string UNRESERVED = 'a-zA-Z0-9_\-.~';
    private const string SUB_DELIMS = '!$&\'()*+,;=';

    private string $scheme = '';

    private string $userInfo = '';

    private string $host = '';

    private ?int $port = null;

    private string $path = '';

    private string $query = '';

    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            return;
        }
        $parts = parse_url($uri);
        if ($parts === false) {
            throw new \InvalidArgumentException(sprintf('Invalid URI "%s".', $uri));
        }

        $this->scheme = strtolower($parts['scheme'] ?? '');
        $this->userInfo = $this->encodeUserInfo($parts['user'] ?? '')
            . (isset($parts['pass']) ? ':' . $this->encodeUserInfo($parts['pass']) : '');
        $this->host = strtolower($parts['host'] ?? '');
        $this->port = $this->filterPort($parts['port'] ?? null);
        $this->path = $this->encodePath($parts['path'] ?? '');
        $this->query = $this->encodeQueryOrFragment($parts['query'] ?? '');
        $this->fragment = $this->encodeQueryOrFragment($parts['fragment'] ?? '');
    }

    #[\Override]
    public function __toString(): string
    {
        $uri = '';
        if ($this->scheme !== '') {
            $uri .= $this->scheme . ':';
        }
        $authority = $this->getAuthority();
        if ($authority !== '' || $this->scheme === 'file') {
            $uri .= '//' . $authority;
        }

        $path = $this->path;
        if ($authority !== '' && $path !== '' && $path[0] !== '/') {
            $path = '/' . $path; // RFC 3986 §3.3
        } elseif ($authority === '' && str_starts_with($path, '//')) {
            $path = '/' . ltrim($path, '/');
        }
        $uri .= $path;

        if ($this->query !== '') {
            $uri .= '?' . $this->query;
        }
        if ($this->fragment !== '') {
            $uri .= '#' . $this->fragment;
        }

        return $uri;
    }

    #[\Override]
    public function getScheme(): string
    {
        return $this->scheme;
    }

    #[\Override]
    public function getAuthority(): string
    {
        if ($this->host === '') {
            return '';
        }
        $authority = ($this->userInfo !== '' ? $this->userInfo . '@' : '') . $this->host;

        return $this->port !== null ? $authority . ':' . $this->port : $authority;
    }

    #[\Override]
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    #[\Override]
    public function getHost(): string
    {
        return $this->host;
    }

    #[\Override]
    public function getPort(): ?int
    {
        return $this->port;
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->path;
    }

    #[\Override]
    public function getQuery(): string
    {
        return $this->query;
    }

    #[\Override]
    public function getFragment(): string
    {
        return $this->fragment;
    }

    #[\Override]
    public function withScheme(string $scheme): static
    {
        $clone = clone $this;
        $clone->scheme = strtolower($scheme);
        $clone->port = $clone->filterPort($this->port);

        return $clone;
    }

    #[\Override]
    public function withUserInfo(string $user, ?string $password = null): static
    {
        $clone = clone $this;
        $clone->userInfo = $this->encodeUserInfo($user)
            . ($password !== null && $password !== '' && $user !== '' ? ':' . $this->encodeUserInfo($password) : '');

        return $clone;
    }

    #[\Override]
    public function withHost(string $host): static
    {
        $clone = clone $this;
        $clone->host = strtolower($host);

        return $clone;
    }

    #[\Override]
    public function withPort(?int $port): static
    {
        $clone = clone $this;
        $clone->port = $clone->filterPort($port);

        return $clone;
    }

    #[\Override]
    public function withPath(string $path): static
    {
        $clone = clone $this;
        $clone->path = $this->encodePath($path);

        return $clone;
    }

    #[\Override]
    public function withQuery(string $query): static
    {
        $clone = clone $this;
        $clone->query = $this->encodeQueryOrFragment(ltrim($query, '?'));

        return $clone;
    }

    #[\Override]
    public function withFragment(string $fragment): static
    {
        $clone = clone $this;
        $clone->fragment = $this->encodeQueryOrFragment(ltrim($fragment, '#'));

        return $clone;
    }

    private function filterPort(?int $port): ?int
    {
        if ($port === null) {
            return null;
        }
        if ($port < 0 || $port > 65535) {
            throw new \InvalidArgumentException(sprintf('Invalid port %d.', $port));
        }

        return (self::DEFAULT_PORTS[$this->scheme] ?? null) === $port ? null : $port;
    }

    /**
     * Percent-encodes characters that are not allowed, keeping existing percent-encodings.
     */
    private function encode(string $value, string $allowed): string
    {
        return (string) preg_replace_callback(
            '/(?:[^' . self::UNRESERVED . preg_quote(self::SUB_DELIMS, '/') . $allowed . '%]++|%(?![A-Fa-f0-9]{2}))/',
            static fn (array $match): string => rawurlencode($match[0]),
            $value
        );
    }

    private function encodePath(string $path): string
    {
        return $this->encode($path, ':@\/');
    }

    private function encodeQueryOrFragment(string $value): string
    {
        return $this->encode($value, ':@\/\?');
    }

    private function encodeUserInfo(string $value): string
    {
        return $this->encode($value, '');
    }
}

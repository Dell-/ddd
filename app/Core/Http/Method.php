<?php

declare(strict_types=1);

namespace Core\Http;

/**
 * Request methods of RFC 9110 §9 and RFC 5789 (PATCH).
 */
enum Method: string
{
    case Get = 'GET';
    case Head = 'HEAD';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';
    case Options = 'OPTIONS';
    case Trace = 'TRACE';
    case Connect = 'CONNECT';

    /**
     * Method names are case-sensitive (RFC 9110 §9.1), but clients often send lower case.
     */
    public static function fromName(string $name): ?self
    {
        return self::tryFrom(strtoupper($name));
    }

    /**
     * Read-only semantics (RFC 9110 §9.2.1): no CSRF protection is needed.
     */
    public function isSafe(): bool
    {
        return match ($this) {
            self::Get, self::Head, self::Options, self::Trace => true,
            default => false,
        };
    }

    /**
     * Repeating the request has the same effect (RFC 9110 §9.2.2).
     */
    public function isIdempotent(): bool
    {
        return $this->isSafe() || $this === self::Put || $this === self::Delete;
    }

    /**
     * RFC 9110 §9.2.3 (POST/PATCH only with explicit freshness information, not considered here).
     */
    public function isCacheable(): bool
    {
        return $this === self::Get || $this === self::Head;
    }
}

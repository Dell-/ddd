<?php

declare(strict_types=1);

namespace Core\Route;

/**
 * What happens when a path differs from the route's path only by the trailing slash
 * ("/items/" for "/items", "/docs" for "/docs/"). Set in routes.xml:
 *
 *     <routes trailing-slash="redirect">                         default of all routes (redirect if omitted)
 *         <route name="api" ... trailing-slash="strict"/>         per route
 */
enum TrailingSlash: string
{
    /** Redirect to the configured form: 301 for GET/HEAD, 308 otherwise (keeps method and body). One URL per page. */
    case Redirect = 'redirect';

    /** Only the configured form exists, the other one is 404. */
    case Strict = 'strict';

    /** Both forms are served (no redirect); generated URLs use the configured form. Duplicate URLs for search engines. */
    case Ignore = 'ignore';

    public const self DEFAULT = self::Redirect;

    /**
     * The same path with the trailing slash added or removed ("/" has no other form).
     */
    public static function toggle(string $path): ?string
    {
        if ($path === '' || $path === '/') {
            return null;
        }

        return str_ends_with($path, '/') ? (rtrim($path, '/') ?: null) : $path . '/';
    }
}

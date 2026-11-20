<?php

declare(strict_types=1);

namespace Core\Route;

/**
 * Result of a successful route lookup.
 */
final readonly class RouteMatch
{
    /**
     * @param array<string, string> $parameters decoded path parameters
     * @param array<array-key, mixed> $query query parameters with the declared defaults
     */
    public function __construct(
        public Route $route,
        public array $parameters = [],
        public array $query = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Core\Route;

use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\HttpException;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotFoundException;
use Core\Http\Method;
use Core\Http\Status;

/**
 * Finds routes for HTTP requests and console commands and generates URLs.
 *
 * - Routes are checked in declaration order, the first match wins; paths are matched exactly, the other
 *   form of a path (trailing slash) is handled by the route's TrailingSlash policy: "ignore" routes match
 *   it here, "redirect" routes are found by findCanonicalPath() (RouteHandler redirects), "strict" routes 404.
 * - HEAD is served by a GET route unless there is a HEAD route (RFC 9110 §9.3.2).
 * - An existing path with another method is 405 with Allow (OPTIONS is answered by RouteHandler).
 * - Declared query parameters are validated (400) and get their defaults.
 * - Percent-encoded paths are decoded before matching except "%2F" and "%25": an encoded slash stays
 *   inside one parameter and nothing is decoded twice.
 */
class Router implements UrlGeneratorInterface
{
    private const array METHOD_ORDER = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @param string $path URL path as received (percent-encoded)
     * @param array<array-key, mixed> $query
     * @throws NotFoundException|MethodNotAllowedException|BadRequestException
     */
    public function match(string $method, string $path, array $query = []): RouteMatch
    {
        $method = strtoupper($method);
        $decoded = self::decodePath($path === '' ? '/' : $path);
        $alternate = TrailingSlash::toggle($decoded);

        $fallback = null;
        $allowed = [];
        foreach ($this->config->getRoutes() as $route) {
            $parameters = $route->match($decoded);
            if ($parameters === null && $alternate !== null && $route->trailingSlash === TrailingSlash::Ignore) {
                $parameters = $route->match($alternate);
            }
            if ($parameters === null) {
                continue;
            }
            $parameters = array_map(self::decodeParameter(...), $parameters);
            if ($route->allowsMethod($method)) {
                return $this->withQuery($route, $parameters, $query);
            }
            if ($method === Method::Head->value && $fallback === null && $route->allowsMethod(Method::Get->value)) {
                $fallback = [$route, $parameters];
            }
            array_push($allowed, ...$route->methods);
        }

        if ($fallback !== null) {
            return $this->withQuery($fallback[0], $fallback[1], $query);
        }
        if ($allowed === []) {
            throw new NotFoundException(sprintf('No route for "%s %s".', $method, $decoded));
        }

        throw new MethodNotAllowedException(self::sortMethods($allowed), sprintf('Method %s is not allowed for "%s".', $method, $decoded));
    }

    /**
     * Methods allowed for the path (with implicit HEAD and OPTIONS), empty if no route has the path.
     *
     * @return list<string>
     */
    public function getAllowedMethods(string $path): array
    {
        return self::sortMethodsOrEmpty($this->routesFor(self::decodePath($path)));
    }

    /**
     * The configured form of a path that differs only by the trailing slash, if a route with the
     * "redirect" policy has it; null otherwise. RouteHandler redirects to it.
     */
    public function findCanonicalPath(string $path): ?string
    {
        $candidate = TrailingSlash::toggle($path);
        if ($candidate === null) {
            return null;
        }
        $decoded = self::decodePath($candidate);

        return array_any(
            $this->config->getRoutes(),
            static fn (Route $route): bool => $route->trailingSlash === TrailingSlash::Redirect && $route->match($decoded) !== null
        ) ? $candidate : null;
    }

    /**
     * @return list<string> methods of the routes that have the (decoded) path
     */
    private function routesFor(string $decoded): array
    {
        $alternate = TrailingSlash::toggle($decoded);
        $methods = [];
        foreach ($this->config->getRoutes() as $route) {
            $matches = $route->match($decoded) !== null
                || ($alternate !== null && $route->trailingSlash === TrailingSlash::Ignore && $route->match($alternate) !== null);
            if ($matches) {
                array_push($methods, ...$route->methods);
            }
        }

        return $methods;
    }

    /**
     * @param list<string> $methods
     * @return list<string>
     */
    private static function sortMethodsOrEmpty(array $methods): array
    {
        return $methods === [] ? [] : self::sortMethods($methods);
    }

    /**
     * Action class of the console command.
     *
     * @throws NotFoundException
     */
    public function matchCommand(string $name): string
    {
        return $this->config->getCommand($name) ?? throw new NotFoundException(sprintf('Command "%s" not found.', $name));
    }

    #[\Override]
    public function generate(string $name, array $parameters = []): string
    {
        $route = $this->config->getRoute($name) ?? throw new \InvalidArgumentException(sprintf('Route "%s" does not exist.', $name));

        return $route->generate($parameters);
    }

    /**
     * @param array<string, string> $parameters
     * @param array<array-key, mixed> $query
     */
    private function withQuery(Route $route, array $parameters, array $query): RouteMatch
    {
        [$query, $errors] = $route->matchQuery($query);
        if ($errors !== []) {
            $details = array_map(static fn (string $name, string $error): string => sprintf('"%s" %s', $name, $error), array_keys($errors), $errors);
            throw new HttpException(
                Status::BadRequest,
                'Invalid query parameters: ' . implode('; ', $details) . '.',
                extensions: ['errors' => $errors]
            );
        }

        return new RouteMatch($route, $parameters, $query);
    }

    /**
     * @param list<string> $methods
     * @return list<string>
     */
    private static function sortMethods(array $methods): array
    {
        $methods[] = Method::Options->value;
        if (in_array(Method::Get->value, $methods, true)) {
            $methods[] = Method::Head->value;
        }
        $order = array_flip(self::METHOD_ORDER);
        $methods = array_values(array_unique($methods));
        usort($methods, static fn (string $a, string $b): int => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);

        return $methods;
    }

    private static function decodePath(string $path): string
    {
        return (string) preg_replace_callback(
            '/%([0-9A-Fa-f]{2})/',
            static fn (array $m): string => in_array(strtoupper($m[1]), ['2F', '25'], true) ? $m[0] : chr((int) hexdec($m[1])),
            $path
        );
    }

    private static function decodeParameter(string $value): string
    {
        return (string) preg_replace_callback('/%(2F|25)/i', static fn (array $m): string => strtoupper($m[1]) === '2F' ? '/' : '%', $value);
    }
}

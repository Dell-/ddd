<?php

declare(strict_types=1);

namespace Core\Route;

/**
 * HTTP route: name, methods, path pattern, declared query parameters and action class.
 *
 * Path pattern: static text and parameters "{name}" (one path segment) or "{name:regex}",
 * e.g. "/items/{id:\d+}" or "/files/{path:.+}". The path is matched exactly; what happens to the
 * other form of the path ("/items/" for "/items") is the route's TrailingSlash policy.
 */
final class Route
{
    public const string DEFAULT_REQUIREMENT = '[^/]+';

    /** @var list<string> */
    public private(set) array $methods;

    public private(set) string $path;

    /** @var array<string, QueryParameter> */
    public private(set) array $query = [];

    /** @var list<string> */
    public array $parameterNames {
        get => array_values(array_map(static fn (array $token): string => $token[0], array_filter($this->tokens, is_array(...))));
    }

    private readonly string $regex;

    /**
     * Static text (string) and parameters ([name, regex]) in path order.
     *
     * @var list<string|array{string, string}>
     */
    private readonly array $tokens;

    /**
     * @param list<string> $methods
     * @param list<QueryParameter> $query
     */
    public function __construct(
        public private(set) string $name,
        array $methods,
        string $path,
        public private(set) string $action,
        array $query = [],
        public private(set) TrailingSlash $trailingSlash = TrailingSlash::DEFAULT,
    ) {
        if ($methods === []) {
            throw new \InvalidArgumentException(sprintf('Route "%s" has no methods.', $name));
        }
        if (!str_starts_with($path, '/')) {
            throw new \InvalidArgumentException(sprintf('The path of route "%s" must start with "/".', $name));
        }
        $this->methods = array_values(array_unique(array_map(strtoupper(...), $methods)));
        $this->path = $path;
        $this->tokens = $this->tokenize($path);
        $this->regex = $this->compile($this->tokens);

        foreach ($query as $parameter) {
            if (in_array($parameter->name, $this->parameterNames, true)) {
                throw new \InvalidArgumentException(sprintf('Route "%s": "%s" is a path and a query parameter.', $name, $parameter->name));
            }
            $this->query[$parameter->name] = $parameter;
        }
    }

    public function allowsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods, true);
    }

    /**
     * Matches a decoded path (see Router) and returns the path parameters, or null.
     *
     * @return array<string, string>|null
     */
    public function match(string $path): ?array
    {
        if (!preg_match($this->regex, $path, $matches)) {
            return null;
        }

        return array_intersect_key($matches, array_flip($this->parameterNames));
    }

    /**
     * Validates the declared query parameters and adds their defaults.
     *
     * @param array<array-key, mixed> $query
     * @return array{array<array-key, mixed>, array<string, string>} [query with defaults, errors by name]
     */
    public function matchQuery(array $query): array
    {
        $errors = [];
        foreach ($this->query as $name => $parameter) {
            $error = $parameter->validate($query[$name] ?? null);
            if ($error !== null) {
                $errors[$name] = $error;
            } elseif (($query[$name] ?? '') === '' && $parameter->default !== null) {
                $query[$name] = $parameter->default;
            }
        }

        return [$query, $errors];
    }

    /**
     * Builds the URL (path and query string) of the route. Path parameters go into the path, declared
     * query parameters into the query string; anything else is an error, so URLs always follow the config.
     *
     * @param array<string, mixed> $parameters
     */
    public function generate(array $parameters): string
    {
        $undeclared = array_diff(array_keys($parameters), $this->parameterNames, array_keys($this->query));
        if ($undeclared !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Route "%s" has no parameter "%s" (path: %s; query: %s).',
                $this->name,
                implode('", "', $undeclared),
                implode(', ', $this->parameterNames) ?: '-',
                implode(', ', array_keys($this->query)) ?: '-'
            ));
        }

        $path = '';
        foreach ($this->tokens as $token) {
            if (is_string($token)) {
                $path .= $token;
                continue;
            }
            [$name, $regex] = $token;
            if (!array_key_exists($name, $parameters)) {
                throw new \InvalidArgumentException(sprintf('Route "%s" requires path parameter "%s".', $this->name, $name));
            }
            $path .= $this->encodeParameter($name, $regex, (string) $parameters[$name]);
        }

        $query = [];
        foreach ($this->query as $name => $parameter) {
            $value = $parameters[$name] ?? null;
            $value = is_bool($value) ? (int) $value : $value;
            $value = is_scalar($value) ? (string) $value : $value;
            $error = $parameter->validate($value);
            if ($error !== null) {
                throw new \InvalidArgumentException(sprintf('Query parameter "%s" of route "%s" %s.', $name, $this->name, $error));
            }
            if ($value !== null && $value !== '') {
                $query[$name] = $value;
            }
        }

        return $query === [] ? $path : $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * The value as it appears in the path: "%" stays encoded, "/" is kept if the regex allows it
     * (multi-segment parameter), otherwise it is encoded as "%2F".
     */
    private function encodeParameter(string $name, string $regex, string $value): string
    {
        $matches = static fn (string $form): bool => (bool) preg_match('#^(?:' . $regex . ')$#Du', $form);

        if ($matches(str_replace('%', '%25', $value))) {
            return str_replace('%2F', '/', rawurlencode($value));
        }
        if (str_contains($value, '/') && $matches(str_replace(['%', '/'], ['%25', '%2F'], $value))) {
            return rawurlencode($value);
        }

        throw new \InvalidArgumentException(sprintf('Parameter "%s" of route "%s" must match "%s", "%s" given.', $name, $this->name, $regex, $value));
    }

    /**
     * @return list<string|array{string, string}>
     */
    private function tokenize(string $path): array
    {
        $tokens = [];
        $static = '';
        $length = strlen($path);
        for ($i = 0; $i < $length; $i++) {
            $char = $path[$i];
            if ($char === '}') {
                throw $this->invalid($path, 'unexpected "}"');
            }
            if ($char !== '{') {
                $static .= $char;
                continue;
            }

            // the regex may contain {n,m}: find the matching "}"
            $depth = 1;
            $end = $i + 1;
            while ($end < $length && $depth > 0) {
                $depth += match ($path[$end]) { '{' => 1, '}' => -1, default => 0 };
                $end++;
            }
            if ($depth !== 0) {
                throw $this->invalid($path, 'unclosed "{"');
            }

            [$name, $regex] = array_pad(explode(':', substr($path, $i + 1, $end - $i - 2), 2), 2, self::DEFAULT_REQUIREMENT);
            if (!preg_match('/^[A-Za-z_]\w*$/', $name)) {
                throw $this->invalid($path, sprintf('invalid parameter name "%s"', $name));
            }
            if (array_any($tokens, static fn (string|array $token): bool => is_array($token) && $token[0] === $name)) {
                throw $this->invalid($path, sprintf('duplicate parameter "%s"', $name));
            }
            if ($regex === '' || @preg_match('#' . $regex . '#u', '') === false) {
                throw $this->invalid($path, sprintf('invalid regex "%s" of parameter "%s"', $regex, $name));
            }

            if ($static !== '') {
                $tokens[] = $static;
                $static = '';
            }
            $tokens[] = [$name, $regex];
            $i = $end - 1;
        }
        if ($static !== '') {
            $tokens[] = $static;
        }

        return $tokens;
    }

    /**
     * @param list<string|array{string, string}> $tokens
     */
    private function compile(array $tokens): string
    {
        $regex = '';
        foreach ($tokens as $token) {
            $regex .= is_string($token) ? preg_quote($token, '#') : sprintf('(?P<%s>%s)', $token[0], $token[1]);
        }

        return '#^' . $regex . '$#Du';
    }

    private function invalid(string $path, string $reason): \InvalidArgumentException
    {
        return new \InvalidArgumentException(sprintf('Invalid path "%s" of route "%s": %s.', $path, $this->name, $reason));
    }
}

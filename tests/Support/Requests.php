<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Http\Message\ServerRequest;
use Core\Http\Message\ServerRequestFactory;
use Core\Http\Message\Stream;

/**
 * Server requests for tests, built like the real ones (ServerRequestFactory).
 */
final class Requests
{
    /**
     * @param array<string, string> $headers
     * @param array<string, string> $cookies
     */
    public static function make(
        string $method = 'GET',
        string $uri = '/',
        array $headers = [],
        string $body = '',
        array $cookies = [],
    ): ServerRequest {
        $parts = parse_url($uri);
        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''),
            'HTTP_HOST' => ($parts['host'] ?? 'localhost') . (isset($parts['port']) ? ':' . $parts['port'] : ''),
            'HTTPS' => ($parts['scheme'] ?? 'http') === 'https' ? 'on' : '',
        ];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $server[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_' . $key] = $value;
        }
        parse_str($parts['query'] ?? '', $query);

        return ServerRequestFactory::fromArrays($server, $query, [], $cookies, [], Stream::create($body));
    }
}

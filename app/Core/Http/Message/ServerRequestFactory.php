<?php

declare(strict_types=1);

namespace Core\Http\Message;

/**
 * Builds the server request from PHP's globals.
 *
 * The parsed body is set only for what PHP parses itself (form and multipart POST);
 * everything else (JSON, JSON Lines, XML, multipart PUT/PATCH, ...) is parsed by BodyParsingMiddleware.
 */
final class ServerRequestFactory
{
    public static function fromGlobals(): ServerRequest
    {
        return self::fromArrays(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES,
            Stream::fromFile('php://input', 'rb')
        );
    }

    /**
     * @param array<string, mixed> $server
     * @param array<array-key, mixed> $query
     * @param array<array-key, mixed> $post
     * @param array<string, string> $cookies
     * @param array<string, mixed> $files in $_FILES format
     */
    public static function fromArrays(
        array $server,
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $files = [],
        ?Stream $body = null,
    ): ServerRequest {
        $method = (string) ($server['REQUEST_METHOD'] ?? 'GET');
        $protocol = preg_match('#^HTTP/(\d(?:\.\d)?)$#', (string) ($server['SERVER_PROTOCOL'] ?? ''), $m) ? $m[1] : '1.1';

        $request = new ServerRequest($method, self::uri($server), $server, self::headers($server), $body, $protocol)
            ->withQueryParams($query)
            ->withCookieParams($cookies)
            ->withUploadedFiles(self::normalizeFiles($files));

        $mediaType = strtolower(trim(explode(';', $request->getHeaderLine('Content-Type'), 2)[0]));
        if ($method === 'POST' && in_array($mediaType, ['application/x-www-form-urlencoded', 'multipart/form-data'], true)) {
            $request = $request->withParsedBody($post);
        }

        return $request;
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function uri(array $server): Uri
    {
        $https = strtolower((string) ($server['HTTPS'] ?? ''));
        $scheme = $https !== '' && $https !== 'off' ? 'https' : 'http';

        $host = (string) ($server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? $server['SERVER_ADDR'] ?? 'localhost');
        $port = null;
        if (preg_match('/^(\[[^\]]+\]|[^:]+)(?::(\d+))?$/', $host, $matches)) {
            $host = $matches[1];
            $port = isset($matches[2]) ? (int) $matches[2] : null;
        }
        $port ??= isset($server['SERVER_PORT']) ? (int) $server['SERVER_PORT'] : null;

        $target = (string) ($server['REQUEST_URI'] ?? '/');
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $target)) {
            $absolute = new Uri($target); // absolute-form (RFC 9112 §3.2.2)
            $target = $absolute->getPath() . ($absolute->getQuery() !== '' ? '?' . $absolute->getQuery() : '');
        }
        [$path, $queryString] = array_pad(explode('?', explode('#', $target, 2)[0], 2), 2, '');

        return new Uri()
            ->withScheme($scheme)
            ->withHost($host)
            ->withPort($port)
            ->withPath('/' . ltrim($path, '/'))
            ->withQuery($queryString !== '' ? $queryString : (string) ($server['QUERY_STRING'] ?? ''));
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function headers(array $server): array
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (!is_scalar($value) || $value === '') {
                continue;
            }
            $key = (string) $key;
            if (str_starts_with($key, 'HTTP_')) {
                $name = substr($key, 5);
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = $key;
            } else {
                continue;
            }
            $headers[ucwords(strtolower(str_replace('_', '-', $name)), '-')] = (string) $value;
        }

        // Authorization is hidden from $_SERVER by some SAPIs
        if (!isset($headers['Authorization'])) {
            if (isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = (string) $server['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($server['PHP_AUTH_USER'])) {
                $headers['Authorization'] = 'Basic ' . base64_encode($server['PHP_AUTH_USER'] . ':' . ($server['PHP_AUTH_PW'] ?? ''));
            }
        }

        return $headers;
    }

    /**
     * $_FILES (including the "name[a][b]" layout) as a tree of UploadedFile.
     *
     * @param array<string, mixed> $files
     * @return array<array-key, mixed>
     */
    private static function normalizeFiles(array $files): array
    {
        $result = [];
        foreach ($files as $field => $spec) {
            if ($spec instanceof \Psr\Http\Message\UploadedFileInterface) {
                $result[$field] = $spec;
            } elseif (is_array($spec) && isset($spec['tmp_name'], $spec['error'])) {
                $result[$field] = self::fileTree($spec['tmp_name'], $spec['size'] ?? null, $spec['error'], $spec['name'] ?? null, $spec['type'] ?? null);
            } elseif (is_array($spec)) {
                $result[$field] = self::normalizeFiles($spec);
            }
        }

        return $result;
    }

    private static function fileTree(mixed $tmpName, mixed $size, mixed $error, mixed $name, mixed $type): UploadedFile|array
    {
        if (!is_array($tmpName)) {
            return new UploadedFile(
                (string) $tmpName,
                is_numeric($size) ? (int) $size : null,
                (int) $error,
                is_string($name) && $name !== '' ? $name : null,
                is_string($type) && $type !== '' ? $type : null
            );
        }

        $tree = [];
        foreach ($tmpName as $key => $value) {
            $tree[$key] = self::fileTree($value, $size[$key] ?? null, $error[$key] ?? UPLOAD_ERR_NO_FILE, $name[$key] ?? null, $type[$key] ?? null);
        }

        return $tree;
    }
}

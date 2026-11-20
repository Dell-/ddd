<?php

declare(strict_types=1);

namespace Core\Request\Http;

use Core\Request\RequestException;

/**
 * Reads the request data of the current HTTP request.
 */
class Parser
{
    public const HTTP_GET_REQUEST = 'GET';
    public const HTTP_POST_REQUEST = 'POST';
    public const HTTP_PUT_REQUEST = 'PUT';
    public const HTTP_PATCH_REQUEST = 'PATCH';
    public const HTTP_DELETE_REQUEST = 'DELETE';
    public const HTTP_HEAD_REQUEST = 'HEAD';
    public const HTTP_OPTIONS_REQUEST = 'OPTIONS';

    /**
     * @return array<array-key, mixed>
     * @throws RequestException
     */
    public function parse(string $method): array
    {
        return match (strtoupper($method)) {
            self::HTTP_GET_REQUEST,
            self::HTTP_HEAD_REQUEST,
            self::HTTP_OPTIONS_REQUEST,
            self::HTTP_DELETE_REQUEST => $_GET,
            self::HTTP_POST_REQUEST => $_POST !== [] ? $_POST : $this->parseBody(),
            self::HTTP_PUT_REQUEST,
            self::HTTP_PATCH_REQUEST => $this->parseBody(),
            default => throw new RequestException(sprintf('Unsupported HTTP method "%s".', $method), 405),
        };
    }

    /**
     * @return array<array-key, mixed>
     * @throws RequestException
     */
    private function parseBody(): array
    {
        $body = (string) file_get_contents('php://input');
        if ($body === '') {
            return [];
        }

        if (str_contains(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'json')) {
            try {
                $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new RequestException('Invalid JSON body: ' . $e->getMessage(), 400, $e);
            }

            return is_array($data) ? $data : [];
        }

        parse_str($body, $data);

        return $data;
    }
}

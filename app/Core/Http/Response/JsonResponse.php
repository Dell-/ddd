<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Status;

/**
 * JSON response (RFC 8259, UTF-8). Use $mediaType for "+json" types, e.g. application/problem+json.
 */
class JsonResponse extends Response
{
    public const int DEFAULT_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param array<string, string|list<string>> $headers
     * @throws \JsonException
     */
    public function __construct(
        mixed $data,
        Status|int $status = Status::Ok,
        array $headers = [],
        int $flags = self::DEFAULT_FLAGS,
        string $mediaType = 'application/json',
    ) {
        parent::__construct($status, ['Content-Type' => $mediaType] + $headers, json_encode($data, $flags | JSON_THROW_ON_ERROR));
    }
}

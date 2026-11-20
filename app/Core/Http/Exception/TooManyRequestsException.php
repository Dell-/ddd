<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 429 Too Many Requests (RFC 6585 §4). Retry-After (RFC 9110 §10.2.3): seconds or a date.
 */
class TooManyRequestsException extends ClientErrorException
{
    public function __construct(int|\DateTimeInterface|null $retryAfter = null, ?string $detail = null, ?\Throwable $previous = null)
    {
        $headers = match (true) {
            $retryAfter === null => [],
            is_int($retryAfter) => ['Retry-After' => (string) max(0, $retryAfter)],
            default => ['Retry-After' => gmdate('D, d M Y H:i:s', $retryAfter->getTimestamp()) . ' GMT'],
        };

        parent::__construct(Status::TooManyRequests, $detail, $headers, $previous);
    }
}

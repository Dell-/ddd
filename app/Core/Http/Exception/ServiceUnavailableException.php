<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 503 Service Unavailable (RFC 9110 §15.6.4). Retry-After (RFC 9110 §10.2.3): seconds or a date.
 */
class ServiceUnavailableException extends ServerErrorException
{
    public function __construct(int|\DateTimeInterface|null $retryAfter = null, ?string $detail = null, ?\Throwable $previous = null)
    {
        $headers = match (true) {
            $retryAfter === null => [],
            is_int($retryAfter) => ['Retry-After' => (string) max(0, $retryAfter)],
            default => ['Retry-After' => gmdate('D, d M Y H:i:s', $retryAfter->getTimestamp()) . ' GMT'],
        };

        parent::__construct(Status::ServiceUnavailable, $detail, $headers, $previous);
    }
}

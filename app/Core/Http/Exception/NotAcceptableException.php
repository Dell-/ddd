<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 406 Not Acceptable: none of the available representations matches the Accept header (RFC 9110 §15.5.7).
 */
class NotAcceptableException extends ClientErrorException
{
    /**
     * @param list<string> $available media types the server can produce
     */
    public function __construct(private readonly array $available = [], ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(
            Status::NotAcceptable,
            $detail ?? ($available === [] ? null : 'Available media types: ' . implode(', ', $available) . '.'),
            [],
            $previous,
            extensions: $available === [] ? [] : ['available' => $available]
        );
    }

    /**
     * @return list<string>
     */
    public function getAvailable(): array
    {
        return $this->available;
    }
}

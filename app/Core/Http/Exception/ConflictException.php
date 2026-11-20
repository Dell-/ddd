<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 409 Conflict
 */
class ConflictException extends ClientErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::Conflict, $detail, $headers, $previous);
    }
}

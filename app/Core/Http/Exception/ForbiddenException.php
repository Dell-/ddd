<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 403 Forbidden
 */
class ForbiddenException extends ClientErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::Forbidden, $detail, $headers, $previous);
    }
}

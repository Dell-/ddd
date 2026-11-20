<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 451 Unavailable For Legal Reasons
 */
class UnavailableForLegalReasonsException extends ClientErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::UnavailableForLegalReasons, $detail, $headers, $previous);
    }
}

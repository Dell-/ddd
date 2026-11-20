<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 431 Request Header Fields Too Large
 */
class RequestHeaderFieldsTooLargeException extends ClientErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::RequestHeaderFieldsTooLarge, $detail, $headers, $previous);
    }
}

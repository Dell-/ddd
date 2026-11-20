<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 502 Bad Gateway
 */
class BadGatewayException extends ServerErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::BadGateway, $detail, $headers, $previous);
    }
}

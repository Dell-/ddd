<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Status;

/**
 * Response without content: 204 No Content by default (also 205, 304 or an empty 202/201).
 */
class EmptyResponse extends Response
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(Status|int $status = Status::NoContent, array $headers = [])
    {
        parent::__construct($status, $headers);
    }
}

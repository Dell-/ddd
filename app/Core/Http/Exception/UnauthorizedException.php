<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 401 Unauthorized. RFC 9110 §15.5.2: the response must contain a WWW-Authenticate challenge.
 */
class UnauthorizedException extends ClientErrorException
{
    /**
     * @param string|list<string> $challenge e.g. 'Bearer realm="api"' or 'Basic realm="admin", charset="UTF-8"'
     */
    public function __construct(string|array $challenge = 'Bearer', ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(Status::Unauthorized, $detail, ['WWW-Authenticate' => $challenge], $previous);
    }
}

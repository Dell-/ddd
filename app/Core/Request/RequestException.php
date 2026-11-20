<?php

declare(strict_types=1);

namespace Core\Request;

/**
 * A request that cannot be served. The code is used as the HTTP status code.
 */
class RequestException extends \RuntimeException
{
    public function __construct(string $message = 'Bad Request', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

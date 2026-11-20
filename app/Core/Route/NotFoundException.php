<?php

declare(strict_types=1);

namespace Core\Route;

use Core\Request\RequestException;

class NotFoundException extends RequestException
{
    public function __construct(string $message = 'Not Found', ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

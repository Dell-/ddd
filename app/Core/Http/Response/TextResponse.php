<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Status;

/**
 * Plain text response (text/plain; charset=utf-8).
 */
class TextResponse extends Response
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(string|\Stringable $content, Status|int $status = Status::Ok, array $headers = [])
    {
        parent::__construct($status, ['Content-Type' => 'text/plain; charset=utf-8'] + $headers, (string) $content);
    }
}

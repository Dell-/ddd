<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Status;
use Psr\Http\Message\UriInterface;

/**
 * Redirect (RFC 9110 §15.4). 302 Found by default; 303 See Other after a form POST,
 * 301/308 for permanent moves (308 and 307 keep the method and the body).
 */
class RedirectResponse extends Response
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(UriInterface|string $location, Status|int $status = Status::Found, array $headers = [])
    {
        $code = Status::code($status);
        if ($code < 300 || $code > 399) {
            throw new \InvalidArgumentException(sprintf('A redirect needs a 3xx status, %d given.', $code));
        }

        parent::__construct($status, ['Location' => (string) $location] + $headers);
    }
}

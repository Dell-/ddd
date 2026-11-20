<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A response that depends on the request (conditional requests, ranges); the router calls prepare().
 */
interface PreparableResponseInterface extends ResponseInterface
{
    public function prepare(ServerRequestInterface $request): ResponseInterface;
}

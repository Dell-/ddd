<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 handler from a closure; remembers the last request (for middleware tests).
 */
final class CallableHandler implements RequestHandlerInterface
{
    public private(set) ?ServerRequestInterface $request = null;

    /**
     * @param (\Closure(ServerRequestInterface): ResponseInterface)|null $handler
     */
    public function __construct(private readonly ?\Closure $handler = null)
    {
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        return $this->handler !== null ? ($this->handler)($request) : new Response(200, [], 'ok');
    }
}

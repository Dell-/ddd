<?php

declare(strict_types=1);

namespace Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware pipeline: the middleware (outermost first) around the final handler.
 * Configured in di.xml; any PSR-15 middleware can be added.
 */
final readonly class Kernel implements RequestHandlerInterface
{
    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private RequestHandlerInterface $handler,
        private array $middleware = [],
    ) {
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->next(0)->handle($request);
    }

    private function next(int $index): RequestHandlerInterface
    {
        $middleware = $this->middleware[$index] ?? null;
        if ($middleware === null) {
            return $this->handler;
        }

        return new class ($middleware, fn (): RequestHandlerInterface => $this->next($index + 1)) implements RequestHandlerInterface {
            /**
             * @param \Closure(): RequestHandlerInterface $next
             */
            public function __construct(private MiddlewareInterface $middleware, private \Closure $next)
            {
            }

            #[\Override]
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->middleware->process($request, ($this->next)());
            }
        };
    }
}

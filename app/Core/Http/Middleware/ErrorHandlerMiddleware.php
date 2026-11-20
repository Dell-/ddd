<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Exception\HttpException;
use Core\Http\Message\ServerRequest;
use Core\Http\Status;
use Core\Http\View\ProblemDetails;
use Core\Http\View\ProblemRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Turns exceptions into problem details responses (RFC 9457). Put it first in the pipeline.
 *
 * HttpException: its status, detail, extensions and headers (logged as notice for 4xx).
 * Anything else: 500 (logged as error); the exception message only in debug mode.
 */
final readonly class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private ProblemRenderer $renderer = new ProblemRenderer(),
        private bool $debug = false,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException $e) {
            $this->logger->log($e->getStatusCode() >= 500 ? 'error' : 'notice', $e->getMessage(), ['exception' => $e]);

            return $this->renderer->render(ProblemDetails::fromException($e), ServerRequest::fromPsr($request), $e->getHeaders());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $problem = ProblemDetails::fromStatus(
                Status::InternalServerError,
                $this->debug ? sprintf('%s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()) : null
            );

            return $this->renderer->render($problem, ServerRequest::fromPsr($request));
        }
    }
}

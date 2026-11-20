<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Exception\ContentTooLargeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 413 Content Too Large (RFC 9110 §15.5.14) for bodies over the limit, checked by Content-Length
 * and by the body size (chunked requests). Put it before BodyParsingMiddleware.
 */
final class BodySizeLimitMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly int $maxBytes = 10485760)
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $length = $request->getHeaderLine('Content-Length');
        $size = ctype_digit($length) ? (int) $length : $request->getBody()->getSize();
        if ($size !== null && $size > $this->maxBytes) {
            throw new ContentTooLargeException(sprintf('The request body must not exceed %d bytes.', $this->maxBytes));
        }

        return $handler->handle($request);
    }
}

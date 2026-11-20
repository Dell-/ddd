<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Method;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Lets POST stand for PUT/PATCH/DELETE (HTML forms only send GET and POST) with the
 * X-HTTP-Method-Override header or the "_method" form field. Safe methods are never tunnelled,
 * so a POST cannot become a cacheable GET. Put it after BodyParsingMiddleware.
 */
final class MethodOverrideMiddleware implements MiddlewareInterface
{
    public const string HEADER = 'X-HTTP-Method-Override';

    public const string FIELD = '_method';

    /**
     * @param list<Method> $allowed
     */
    public function __construct(private readonly array $allowed = [Method::Put, Method::Patch, Method::Delete])
    {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === Method::Post->value) {
            $body = $request->getParsedBody();
            $override = $request->getHeaderLine(self::HEADER);
            if ($override === '' && is_array($body) && is_string($body[self::FIELD] ?? null)) {
                $override = $body[self::FIELD];
            }

            $method = Method::fromName($override);
            if ($method !== null && in_array($method, $this->allowed, true)) {
                $request = $request->withMethod($method->value);
            }
            if (is_array($body) && array_key_exists(self::FIELD, $body)) {
                unset($body[self::FIELD]);
                $request = $request->withParsedBody($body);
            }
        }

        return $handler->handle($request);
    }
}

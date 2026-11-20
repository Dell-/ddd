<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Message\ServerRequest;
use Core\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A plain PSR-15 handler that returns what it knows about the request.
 */
final class EchoRequest implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = ServerRequest::fromPsr($request);
        $body = $request->getParsedBody();

        return new JsonResponse([
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'route' => $request->getRouteName(),
            'params' => $request->getRouteParams(),
            'query' => $request->getQueryParams(),
            'body' => $body instanceof \SimpleXMLElement ? ['xml' => $body->getName(), 'name' => (string) ($body->name ?? '')] : $body,
            'raw' => (string) $request->getBody(),
            'contentType' => $request->getHeaderLine('Content-Type'),
            'test' => $request->getHeaderLine('X-Test'),
            'csrf' => $request->getAttribute('csrf_token') !== null,
        ], headers: ['X-Echo' => 'yes']);
    }
}

<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Di\ContainerInterface;
use Core\Http\Action\ActionInterface;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotFoundException;
use Core\Http\Message\ServerRequest;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\EmptyResponse;
use Core\Http\Response\PreparableResponseInterface;
use Core\Http\Response\RedirectResponse;
use Core\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The innermost request handler: routes the request and runs the action.
 *
 * - Trailing slash: for routes with the "redirect" policy (TrailingSlash, set in routes.xml) the other
 *   form of the path is redirected to the configured one: 301 for GET/HEAD, 308 for other methods
 *   (keeps the method and the body, RFC 9110 §15.4.9). "strict" routes give 404, "ignore" routes match both.
 * - OPTIONS without an OPTIONS route: 204 with Allow (RFC 9110 §9.3.7).
 * - Route parameters become request attributes (and "_route", "_route_params"), the query string gets
 *   the declared defaults.
 * - The action is created by the container: a PSR-15 RequestHandlerInterface or an ActionInterface.
 */
final readonly class RouteHandler implements RequestHandlerInterface
{
    public function __construct(
        private Router $router,
        private ContainerInterface $container,
        private Responder $responder = new Responder(),
    ) {
    }

    #[\Override]
    public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
    {
        $request = ServerRequest::fromPsr($request);
        $path = $request->getUri()->getPath() ?: '/';

        try {
            $match = $this->router->match($request->getMethod(), $path, $request->getQueryParams());
        } catch (NotFoundException $e) {
            $canonical = $this->router->findCanonicalPath($path);
            if ($canonical === null) {
                throw $e;
            }
            $query = $request->getUri()->getQuery();
            $safe = in_array($request->getMethod(), ['GET', 'HEAD'], true);

            return new RedirectResponse(
                $canonical . ($query !== '' ? '?' . $query : ''),
                $safe ? Status::MovedPermanently : Status::PermanentRedirect
            );
        } catch (MethodNotAllowedException $e) {
            if ($request->getMethod() === Method::Options->value) {
                return new EmptyResponse(Status::NoContent, $e->getHeaders());
            }
            throw $e;
        }

        $request = $request
            ->withQueryParams($match->query)
            ->withAttribute(ServerRequestInterface::ATTRIBUTE_ROUTE, $match->route->name)
            ->withAttribute(ServerRequestInterface::ATTRIBUTE_ROUTE_PARAMS, $match->parameters);
        foreach ($match->parameters as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        $action = $this->container->create($match->route->action);
        $response = match (true) {
            $action instanceof RequestHandlerInterface => $action->handle($request),
            $action instanceof ActionInterface => $this->responder->respond($action($request), $request),
            default => throw new \LogicException(sprintf(
                '%s must implement %s or %s.',
                $match->route->action,
                ActionInterface::class,
                RequestHandlerInterface::class
            )),
        };

        return $response instanceof PreparableResponseInterface ? $response->prepare($request) : $response;
    }
}

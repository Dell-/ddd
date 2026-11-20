<?php

declare(strict_types=1);

namespace Core\Request;

use Core\Di\ContainerInterface;
use Core\Route\Config as RouteConfig;
use Core\Route\NotFoundException;
use Core\Service\ActionInterface;

/**
 * Finds the action of the request route and executes it.
 */
class Dispatcher implements HandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RouteConfig $routes,
    ) {
    }

    public function handle(RequestInterface $request): string
    {
        $actionClass = $this->routes->match($request->getMethod(), $request->getRoute());
        if ($actionClass === null) {
            throw new NotFoundException(sprintf('Route "%s %s" not found.', $request->getMethod(), $request->getRoute()));
        }

        $action = $this->container->create($actionClass, ['request' => $request]);
        if (!$action instanceof ActionInterface) {
            throw new \LogicException(sprintf('%s must implement %s.', $actionClass, ActionInterface::class));
        }

        return $action->execute();
    }
}

<?php
/**
 * Copyright © 2016 GBKSOFT. Web and Mobile Software Development.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Core\Request;

use Core\Di\ContainerInterface;

class HandlerFactory
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function create(): HandlerInterface
    {
        $handler = $this->container->get(Dispatcher::class);
        \assert($handler instanceof HandlerInterface);

        return $handler;
    }
}

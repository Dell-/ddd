<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Di\ContainerInterface;

class LayerBuilder
{
    public function create(ContainerInterface $container): Layer
    {
        $layer = $container->create(Layer::class, ['container' => $container]);
        \assert($layer instanceof Layer);

        return $layer;
    }
}

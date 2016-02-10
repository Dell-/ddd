<?php
namespace Core\Service;

use Core\Di\Container;

/**
 * Class LayerBuilder
 */
class LayerBuilder
{
    /**
     * Create layer
     *
     * @param Container $container
     * @return Layer
     */
    protected function create(Container $container)
    {
        return $container->create('Core\Service\Layer', ['container' => $container]);
    }
}

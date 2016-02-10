<?php
namespace Core\Service;

use Core\Di\Container;

/**
 * Class Layer
 */
class Layer
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Constructor
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function collect()
    {

    }

    public function run()
    {

    }
}

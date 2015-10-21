<?php
namespace Application;

use Core\Di\ContainerInterface;
use Core\Service\ServiceInterface;

/**
 * Class Service
 */
final class Service implements ServiceInterface
{
    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Run service
     */
    public function run()
    {
        echo sprintf('time: %f sec', microtime(true) - BEGIN_TIME);
    }
}

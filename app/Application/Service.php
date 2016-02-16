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
     * @var ContainerInterface
     */
    private $container;

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

        echo sprintf(
            'time: %f sec; memory: %d kb',
            microtime(true) - BEGIN_TIME,
            memory_get_usage() / 1024
        );
    }
}

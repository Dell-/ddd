<?php
namespace Application;

use Core\Di\ContainerInterface;
use Core\Service\ServiceInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Run service
     */
    public function run()
    {

        $this->logger->debug(sprintf(
            'time: %f sec; memory: %d kb',
            microtime(true) - BEGIN_TIME,
            memory_get_usage() / 1024
        ), ['callable' => __CLASS__ . '::' . __METHOD__]);
    }
}

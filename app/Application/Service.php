<?php
namespace Application;

use Core\Di\ContainerInterface;
use Core\Request\HandlerFactory;
use Core\Request\RequestFactory;
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
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var HandlerFactory
     */
    private $handlerFactory;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     * @param RequestFactory $requestFactory
     * @param HandlerFactory $handlerFactory
     */
    public function __construct(
        ContainerInterface $container,
        LoggerInterface $logger,
        RequestFactory $requestFactory,
        HandlerFactory $handlerFactory
    ) {
        $this->container = $container;
        $this->logger = $logger;
        $this->requestFactory = $requestFactory;
        $this->handlerFactory = $handlerFactory;
    }

    /**
     * Run service
     */
    public function run()
    {
        $this->handlerFactory->create()
            ->handle($this->requestFactory->create());

        $this->logger->debug(sprintf(
            'time: %f sec; memory: %d kb',
            microtime(true) - BEGIN_TIME,
            memory_get_usage() / 1024
        ), ['callable' => __CLASS__ . '::' . __METHOD__]);
    }
}

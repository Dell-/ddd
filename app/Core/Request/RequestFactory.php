<?php
namespace Core\Request;

use Core\Di\ContainerInterface;

/**
 * Class RequestFactory
 */
class RequestFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $context
     * @return RequestInterface
     */
    public function create($context)
    {
        return $this->container->create($context);
    }
}

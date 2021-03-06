<?php
namespace Core\Request;

use Core\Di\ContainerInterface;

/**
 * Class RequestFactory
 */
class RequestFactory
{
    const CLI_REQUEST = 'cli';

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
     * @return RequestInterface
     */
    public function create()
    {
        if (php_sapi_name() === self::CLI_REQUEST) {
            return $this->handleCliRequest();
        }

        return $this->handleHttpRequest();
    }

    /**
     * @return RequestInterface
     */
    private function handleCliRequest()
    {
        $data = $this->container->get(\Core\Request\Cli\Handler::class)->handle();
        return $this->container->create(
            \Core\Request\Cli\Argument::class,
            ['data' => $data]
        );
    }

    /**
     * @return RequestInterface
     */
    private function handleHttpRequest()
    {
        $data = $this->container->get(\Core\Request\Http\Handler::class)->handle();
        return $this->container->create(
            \Core\Request\Http\Argument::class,
            ['data' => $data]
        );
    }
}

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

//        $test = $this->container->get('Application\ServiceLayer');
        echo sprintf('time: %f sec', microtime(true) - BEGIN_TIME);
//        echo '<pre>';

//        print_r($this->routersConfigReader->read($this->directory));
//        print_r($this->diConfigReader->read($this->directory));
    }

    /**
     * @inheritdoc
     */
    public function getVersion()
    {
        return 'dev-0.1';
    }
}

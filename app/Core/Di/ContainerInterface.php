<?php
namespace Core\Di;

/**
 * Interface ContainerInterface
 */
interface ContainerInterface
{
    /**
     * Get instantiate an object of class
     *
     * @param string $className
     * @return mixed
     */
    public function get($className);

    /**
     * Create new object
     *
     * @param string $className
     * @param array $arguments
     * @return mixed
     */
    public function create($className, array $arguments = []);
}

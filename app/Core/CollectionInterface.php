<?php
namespace Core;

/**
 * Interface CollectionInterface
 */
interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * Return the variable value
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = null);

    /**
     * Set a variable value
     *
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value);

    /**
     * Removes a session variable
     *
     * @param string $key
     * @return mixed
     */
    public function remove($key);

    /**
     * Removes all variables value
     */
    public function removeAll();

    /**
     * Is exists key in array
     *
     * @param mixed $key
     * @return bool
     */
    public function has($key);
}

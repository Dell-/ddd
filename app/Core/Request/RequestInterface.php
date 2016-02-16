<?php
namespace Core\Request;

/**
 * Interface RequestInterface
 */
interface RequestInterface
{
    /**
     * Get data
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function getData($key, $default = null);
}

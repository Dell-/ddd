<?php
namespace Core\Request;

/**
 * Interface RequestInterface
 */
interface RequestInterface
{
    /**
     * @return mixed
     */
    public function getData($key);
}

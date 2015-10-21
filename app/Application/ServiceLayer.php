<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 06.05.15
 * Time: 1:27
 */

namespace Application;

/**
 * Class ServiceLayer
 */
class ServiceLayer
{
    /**
     * @param null $routerConfigReader
     * @param null $diConfigReader
     * @param array $list
     */
    public function __construct($routerConfigReader = null, $diConfigReader = null, array $list = [])
    {
        $this->routerConfigReader = $routerConfigReader;
        $this->diConfigReader = $diConfigReader;
        $this->list = $list;
    }
}

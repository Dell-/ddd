<?php
namespace Application;

/**
 * Class ServiceLayer
 */
class ServiceLayer
{
    /**
     * @param array $list
     */
    public function __construct(array $list = [])
    {
        $this->list = $list;
    }
}

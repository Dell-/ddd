<?php
namespace Core\Service;

/**
 * Interface ActionInterface
 */
interface ActionInterface
{
    /**
     * @return ResultInterface
     */
    public function execute();
}

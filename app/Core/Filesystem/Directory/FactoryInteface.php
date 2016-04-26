<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

/**
 * Interface FactoryInteface
 */
interface FactoryInteface
{
    /**
     * @return DirectoryInterface
     */
    public function create();
}

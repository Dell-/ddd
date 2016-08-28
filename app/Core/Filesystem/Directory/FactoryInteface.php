<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

/**
 * Interface FactoryInteface
 */
interface FactoryInteface
{
    /**
     * @param string $name
     * @return DirectoryInterface
     */
    public function create($name);
}

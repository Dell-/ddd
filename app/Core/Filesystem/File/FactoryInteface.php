<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Interface FactoryInteface
 */
interface FactoryInteface
{
    /**
     * @return FileInterface
     */
    public function create();
}

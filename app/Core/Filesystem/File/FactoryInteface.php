<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Interface FactoryInteface
 */
interface FactoryInteface
{
    /**
     * @param string $filename
     * @return FileInterface
     */
    public function create($filename);
}

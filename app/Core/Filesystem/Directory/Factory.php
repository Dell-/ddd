<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\Directory;
use Core\Filesystem\DirectoryInterface;

/**
 * Class Factory
 */
class Factory implements FactoryInteface
{
    /**
     * @inheritdoc
     */
    public function create($name)
    {
        $directory = new \SplFileInfo($name);
        if (!$directory->isDir()) {
            throw new \InvalidArgumentException('You must specify the directory name.');
        }

        return new Directory($directory);
    }
}

<?php
namespace Core\Filesystem\File;

use Core\Filesystem\File;
use Core\Filesystem\FileInterface;

/**
 * Class Factory
 */
class Factory implements FactoryInteface
{
    /**
     * @inheritdoc
     */
    public function create($filename)
    {
        $file = new \SplFileInfo($filename);
        if (!$file->isFile()) {
            throw new \InvalidArgumentException('You must specify the file name.');
        }

        return new File($file);
    }
}

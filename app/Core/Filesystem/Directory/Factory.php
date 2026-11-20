<?php

declare(strict_types=1);

namespace Core\Filesystem\Directory;

use Core\Filesystem\Directory;
use Core\Filesystem\DirectoryInterface;

class Factory implements FactoryInterface
{
    public function create(string $name): DirectoryInterface
    {
        $directory = new \SplFileInfo($name);
        if (!$directory->isDir()) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a directory.', $name));
        }

        return new Directory($directory);
    }
}

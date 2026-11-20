<?php

declare(strict_types=1);

namespace Core\Filesystem;

class IteratorFactory
{
    public function create(DirectoryInterface $directory): Iterator
    {
        return new Iterator($directory);
    }
}

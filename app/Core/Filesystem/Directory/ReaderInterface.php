<?php

declare(strict_types=1);

namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

interface ReaderInterface
{
    /**
     * @param (callable(DirectoryInterface): bool)|null $filter
     * @return DirectoryInterface[]
     */
    public function read(DirectoryInterface $directory, ?callable $filter = null): array;
}

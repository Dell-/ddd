<?php

declare(strict_types=1);

namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

interface RepositoryInterface
{
    public function save(DirectoryInterface $directory): void;

    public function delete(DirectoryInterface $directory): void;
}

<?php

declare(strict_types=1);

namespace Core\Filesystem\File;

use Core\Filesystem\DirectoryInterface;
use Core\Filesystem\FileInterface;

interface RepositoryInterface
{
    public function save(FileInterface $file, DirectoryInterface $context): void;

    public function delete(FileInterface $file, DirectoryInterface $context): void;
}

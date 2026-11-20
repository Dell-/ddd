<?php

declare(strict_types=1);

namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

interface ReaderInterface
{
    public function read(FileInterface $file): string;
}

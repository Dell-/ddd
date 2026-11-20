<?php

declare(strict_types=1);

namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

interface FactoryInterface
{
    public function create(string $filename): FileInterface;
}

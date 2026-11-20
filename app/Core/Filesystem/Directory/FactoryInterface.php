<?php

declare(strict_types=1);

namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

interface FactoryInterface
{
    public function create(string $name): DirectoryInterface;
}

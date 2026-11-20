<?php

declare(strict_types=1);

namespace Core\Filesystem\File;

use Core\Filesystem\File;
use Core\Filesystem\FileInterface;

class Factory implements FactoryInterface
{
    #[\Override]
    public function create(string $filename): FileInterface
    {
        $file = new \SplFileInfo($filename);
        if (!$file->isFile()) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a file.', $filename));
        }

        return new File($file);
    }
}

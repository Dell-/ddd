<?php

declare(strict_types=1);

namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

class Reader implements ReaderInterface
{
    public function read(FileInterface $file): string
    {
        $content = $file->isReadable() ? file_get_contents($file->getPathname()) : false;
        if ($content === false) {
            throw new \RuntimeException(sprintf('The file "%s" cannot be read.', $file->getPathname()));
        }

        return $content;
    }
}

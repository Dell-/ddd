<?php

declare(strict_types=1);

namespace Core\Filesystem\Filter;

use Core\Filesystem\File as FilesystemFile;

/**
 * Accepts only files and returns them as Core\Filesystem\File.
 */
class File extends \FilterIterator
{
    public function accept(): bool
    {
        return $this->getInnerIterator()->current()->isFile();
    }

    public function current(): FilesystemFile
    {
        return new FilesystemFile($this->getInnerIterator()->current());
    }
}

<?php

declare(strict_types=1);

namespace Core\Filesystem\Filter;

use Core\Filesystem\Directory as FilesystemDirectory;

/**
 * Accepts only directories and returns them as Core\Filesystem\Directory.
 */
class Directory extends \FilterIterator
{
    public function accept(): bool
    {
        return $this->getInnerIterator()->current()->isDir();
    }

    public function current(): FilesystemDirectory
    {
        return new FilesystemDirectory($this->getInnerIterator()->current());
    }
}

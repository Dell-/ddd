<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    /**
     * Read file
     *
     * @param FileInterface $file
     * @return string
     */
    public function read(FileInterface $file);
}

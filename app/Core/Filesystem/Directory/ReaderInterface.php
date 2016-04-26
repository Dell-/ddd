<?php
namespace Core\Filesystem\Directory;

use Core\CollectionInterface;
use Core\Filesystem\DirectoryInterface;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    const FILE_TYPE = 1;

    const DIRECTORY_TYPE = 2;

    /**
     * @param DirectoryInterface $directory
     * @return CollectionInterface
     */
    public function read(DirectoryInterface $directory);
}

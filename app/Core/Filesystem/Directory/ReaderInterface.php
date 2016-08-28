<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;
use Core\Filesystem\FilterInterface;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    /**
     * @param DirectoryInterface $directory
     * @param FilterInterface $filter
     * @return DirectoryInterface[]
     */
    public function read(DirectoryInterface $directory, FilterInterface $filter = null);
}

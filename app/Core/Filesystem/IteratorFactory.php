<?php
namespace Core\Filesystem;

/**
 * Class IteratorFactory
 */
class IteratorFactory
{
    /**
     * @param DirectoryInterface $directory
     * @return Iterator
     */
    public function create(DirectoryInterface $directory)
    {
        return new Iterator($directory);
    }
}

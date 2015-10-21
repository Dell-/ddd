<?php
namespace Core\Filesystem;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    /**
     * Read filesystem
     *
     * @param array $path
     * @return array
     */
    public function read(array $path);
}

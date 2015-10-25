<?php
namespace Core\Filesystem;

/**
 * Interface DirectoryCollectorInterface
 */
interface DirectoryCollectorInterface
{
    /**
     * Read directories in the directory
     *
     * @param DirectoryInterface $directory
     * @return DirectoryInterface[]
     */
    public function collect(DirectoryInterface $directory);
}

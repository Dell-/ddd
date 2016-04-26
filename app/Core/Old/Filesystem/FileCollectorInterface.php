<?php
namespace Core\Filesystem;

/**
 * Interface FileCollectorInterface
 */
interface FileCollectorInterface
{
    /**
     * Read files in the directory
     *
     * @param DirectoryInterface $directory
     * @return FileInterface[]
     */
    public function collect(DirectoryInterface $directory);
}

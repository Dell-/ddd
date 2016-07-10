<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\DirectoryInterface;

/**
 * Interface RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @param DirectoryInterface $directory
     * @return bool
     */
    public function save(DirectoryInterface $directory);

    /**
     * @param DirectoryInterface $directory
     * @return bool
     */
    public function delete(DirectoryInterface $directory);
}

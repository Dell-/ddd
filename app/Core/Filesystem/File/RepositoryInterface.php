<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;
use Core\Filesystem\DirectoryInterface;

/**
 * Interface RepositoryInterface
 */
interface RepositoryInterface
{
    /**
     * @param FileInterface $file
     * @param DirectoryInterface $context
     * @return bool
     */
    public function save(FileInterface $file, DirectoryInterface $context);

    /**
     * @param FileInterface $file
     * @param DirectoryInterface $context
     * @return bool
     */
    public function delete(FileInterface $file, DirectoryInterface $context);
}

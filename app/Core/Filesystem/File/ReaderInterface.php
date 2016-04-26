<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    /**
     * @param int $maxLen
     */
    public function setMaxLen($maxLen);

    /**
     * @param int $offset
     */
    public function setOffset($offset);

    /**
     * Read file
     *
     * @param FileInterface $file
     * @return string
     */
    public function read(FileInterface $file);
}

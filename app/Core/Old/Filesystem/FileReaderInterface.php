<?php
namespace Core\Filesystem;

/**
 * Interface FileReaderInterface
 */
interface FileReaderInterface
{
    /**
     * Read file
     *
     * @param FileInterface $file
     * @return string
     */
    public function read(FileInterface $file);
}

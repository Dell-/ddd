<?php
namespace Core\Filesystem;

/**
 * Class FileFactory
 */
class FileFactory
{
    /**
     * Create directory object
     *
     * @param string $fileName
     * @return FileInterface
     */
    public function create($fileName)
    {
        $file = new \SplFileInfo($fileName);
        if (!$file->isFile()) {
            throw new \InvalidArgumentException('You must specify the file name.');
        }

        return new File($file);
    }
}

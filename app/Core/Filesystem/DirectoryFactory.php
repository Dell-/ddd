<?php
namespace Core\Filesystem;

/**
 * Class DirectoryFactory
 */
class DirectoryFactory
{
    /**
     * Create directory object
     *
     * @param string $directoryName
     * @return DirectoryInterface
     */
    public function create($directoryName)
    {
        $directory = new \SplFileInfo($directoryName);
        if (!$directory->isDir()) {
            throw new \InvalidArgumentException('You must specify the directory name.');
        }

        return new Directory($directory);
    }
}

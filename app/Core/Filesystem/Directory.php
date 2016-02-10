<?php
namespace Core\Filesystem;

/**
 * Class Directory
 */
class Directory implements DirectoryInterface
{
    /**
     * @var \SplFileInfo
     */
    private $directory;

    /**
     * Constructor
     *
     * @param \SplFileInfo $directory
     */
    public function __construct(\SplFileInfo $directory)
    {
        $this->directory = $directory;
    }

    /**
     * Returns true if the directory is readable, otherwise false
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->directory->isReadable();
    }

    /**
     * Returns true if the directory is writable, otherwise false
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->directory->isWritable();
    }

    /**
     * Returns the size of the directory, in bytes
     *
     * @return int
     */
    public function getSize()
    {
        return $this->directory->getSize();
    }

    /**
     * Returns the path and directory name of current directory
     *
     * @return string
     */
    public function getPathname()
    {
        return $this->directory->getPathname();
    }

    /**
     * Returns the path to the directory, omitting the directory name and any trailing slash
     *
     * @return string
     */
    public function getPath()
    {
        return $this->directory->getPath();
    }

    /**
     * Returns the permissions of the directory, as a decimal integer
     *
     * @return int
     */
    public function getPerms()
    {
        return $this->directory->getPerms();
    }

    /**
     * The directory owner of the directory, in numerical format
     *
     * @return int
     */
    public function getOwner()
    {
        return $this->directory->getOwner();
    }

    /**
     * Returns the group id of the current directory in numerical format
     *
     * @return int
     */
    public function getGroup()
    {
        return $this->directory->getGroup();
    }
}

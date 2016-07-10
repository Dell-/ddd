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
        if (!$directory->isDir()) {
            throw new \InvalidArgumentException('This object must indicate to a directory.');
        }
        $this->directory = $directory;
    }

    /**
     * @inheritdoc
     */
    public function isExists()
    {
        return file_exists($this->getPathname());
    }

    /**
     * @inheritdoc
     */
    public function isReadable()
    {
        return $this->directory->isReadable();
    }

    /**
     * @inheritdoc
     */
    public function isWritable()
    {
        return $this->directory->isWritable();
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->directory->getSize();
    }

    /**
     * @inheritdoc
     */
    public function getPathname()
    {
        return $this->directory->getPathname();
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->directory->getPath();
    }

    /**
     * @inheritdoc
     */
    public function getPerms()
    {
        return $this->directory->getPerms();
    }

    /**
     * @inheritdoc
     */
    public function getOwner()
    {
        return $this->directory->getOwner();
    }

    /**
     * @inheritdoc
     */
    public function getGroup()
    {
        return $this->directory->getGroup();
    }
}

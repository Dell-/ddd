<?php
namespace Core\Filesystem;

/**
 * Class File
 */
class File implements FileInterface
{
    /**
     * @var \SplFileInfo
     */
    private $file;

    /**
     * Constructor
     *
     * @param \SplFileInfo $file
     */
    public function __construct(\SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * @inheritdoc
     */
    public function isReadable()
    {
        return $this->file->isReadable();
    }

    /**
     * @inheritdoc
     */
    public function isWritable()
    {
        return $this->file->isWritable();
    }

    /**
     * @inheritdoc
     */
    public function isExecutable()
    {
        return $this->file->isExecutable();
    }

    /**
     * @inheritdoc
     */
    public function getSize()
    {
        return $this->file->getSize();
    }

    /**
     * @inheritdoc
     */
    public function getPathname()
    {
        return $this->file->getPathname();
    }

    /**
     * @inheritdoc
     */
    public function getPath()
    {
        return $this->file->getPath();
    }

    /**
     * @inheritdoc
     */
    public function getPerms()
    {
        return $this->file->getPerms();
    }

    /**
     * @inheritdoc
     */
    public function getOwner()
    {
        return $this->file->getOwner();
    }

    /**
     * @inheritdoc
     */
    public function getGroup()
    {
        return $this->file->getGroup();
    }

    /**
     * @inheritdoc
     */
    public function getMTime()
    {
        return $this->file->getMTime();
    }

    /**
     * @inheritdoc
     */
    public function getCTime()
    {
        return $this->file->getCTime();
    }

    /**
     * @inheritdoc
     */
    public function getATime()
    {
        return $this->file->getATime();
    }

    /**
     * @inheritdoc
     */
    public function getFilename()
    {
        return $this->file->getFilename();
    }

    /**
     * @inheritdoc
     */
    public function getExtension()
    {
        return $this->file->getExtension();
    }
}

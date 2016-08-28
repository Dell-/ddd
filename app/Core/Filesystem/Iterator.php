<?php
namespace Core\Filesystem;

/**
 * Class Iterator
 */
class Iterator implements \SeekableIterator
{
    /**
     * @var \FilesystemIterator
     */
    private $iterator;

    /**
     * Iterator constructor.
     *
     * @param DirectoryInterface $directory
     */
    public function __construct(DirectoryInterface $directory)
    {
        $this->iterator = new \FilesystemIterator(
            $directory->getPathname(),
            \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS
            | \FilesystemIterator::KEY_AS_FILENAME
        );
    }

    /**
     * Cloned encapsulated data
     */
    public function __clone()
    {
        $this->iterator = clone $this->iterator;
    }

    /**
     * @inheritdoc
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * @inheritdoc
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * @inheritdoc
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * @inheritdoc
     */
    public function valid()
    {
        return $this->iterator->valid();
    }

    /**
     * @inheritdoc
     */
    public function rewind()
    {
        $this->iterator->rewind();
    }

    /**
     * @inheritdoc
     */
    public function seek($position)
    {
        $this->iterator->seek($position);
    }
}

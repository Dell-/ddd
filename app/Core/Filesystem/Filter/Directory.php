<?php
namespace Core\Filesystem\Filter;

/**
 * Class Directory
 */
class Directory extends \FilterIterator
{
    /**
     * File constructor.
     *
     * @param \Iterator $iterator
     */
    public function __construct(\Iterator $iterator)
    {
        parent::__construct($iterator);
    }

    /**
     * @inheritdoc
     */
    public function accept()
    {
        /** @var \SplFileInfo $file */
        $file = $this->getInnerIterator()->current();
        return $file->isDir();
    }

    /**
     * @inheritdoc
     * @return \Core\Filesystem\Directory
     */
    public function current()
    {
        return new \Core\Filesystem\Directory($this->getInnerIterator()->current());
    }
}

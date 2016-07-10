<?php
namespace Core\Filesystem\Filter;

/**
 * Class File
 */
class File extends \FilterIterator
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
        return $file->isFile();
    }

    /**
     * @inheritdoc
     * @return \Core\Filesystem\File
     */
    public function current()
    {
        return new \Core\Filesystem\File($this->getInnerIterator()->current());
    }
}

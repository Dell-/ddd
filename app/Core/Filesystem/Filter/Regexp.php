<?php
namespace Core\Filesystem\Filter;

use Core\Filesystem\Iterator;

/**
 * Class Regexp
 */
class Regexp implements \OuterIterator
{
    /**
     * @var \RegexIterator
     */
    private $iterator;

    /**
     * Regexp constructor.
     *
     * @param Iterator $iterator
     * @param string $pattern
     */
    public function __construct(Iterator $iterator, $pattern)
    {
        $this->iterator = new \RegexIterator(
            $iterator,
            $pattern,
            \RegexIterator::MATCH,
            \RegexIterator::USE_KEY
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
     * @return Iterator
     */
    public function getInnerIterator()
    {
        return $this->iterator->getInnerIterator();
    }
}

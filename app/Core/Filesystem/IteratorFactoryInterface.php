<?php
namespace Core\Filesystem;

/**
 * Interface IteratorFactoryInterface
 */
interface IteratorFactoryInterface
{
    /**
     * Create iterator
     *
     * @param \OuterIterator $outerIterator
     * @return \OuterIterator
     */
    public function create(\OuterIterator $outerIterator);
}

<?php

declare(strict_types=1);

namespace Core\Filesystem\Filter;

use Core\Filesystem\Iterator;

/**
 * Accepts directory entries whose file name matches the pattern.
 */
class Regexp implements \OuterIterator
{
    private \RegexIterator $iterator;

    public function __construct(Iterator $iterator, string $pattern)
    {
        $this->iterator = new \RegexIterator($iterator, $pattern, \RegexIterator::MATCH, \RegexIterator::USE_KEY);
    }

    public function __clone()
    {
        $this->iterator = clone $this->iterator;
    }

    public function current(): mixed
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): mixed
    {
        return $this->iterator->key();
    }

    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    public function getInnerIterator(): ?\Iterator
    {
        return $this->iterator->getInnerIterator();
    }
}

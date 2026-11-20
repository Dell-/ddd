<?php

declare(strict_types=1);

namespace Core\Filesystem;

/**
 * Iterates over the entries of a directory (keys are file names, values are \SplFileInfo).
 */
class Iterator implements \SeekableIterator
{
    private \FilesystemIterator $iterator;

    public function __construct(DirectoryInterface $directory)
    {
        $this->iterator = new \FilesystemIterator(
            $directory->getPathname(),
            \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS
            | \FilesystemIterator::KEY_AS_FILENAME
        );
    }

    public function __clone()
    {
        $this->iterator = clone $this->iterator;
    }

    public function current(): \SplFileInfo
    {
        return $this->iterator->current();
    }

    public function next(): void
    {
        $this->iterator->next();
    }

    public function key(): string
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

    public function seek(int $offset): void
    {
        $this->iterator->seek($offset);
    }
}

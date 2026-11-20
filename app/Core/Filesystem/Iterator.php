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

    #[\Override]
    public function current(): \SplFileInfo
    {
        return $this->iterator->current();
    }

    #[\Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[\Override]
    public function key(): string
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->iterator->rewind();
    }

    #[\Override]
    public function seek(int $offset): void
    {
        $this->iterator->seek($offset);
    }
}

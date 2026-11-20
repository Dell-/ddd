<?php

declare(strict_types=1);

namespace Core\Filesystem;

class Directory implements DirectoryInterface
{
    public function __construct(private readonly \SplFileInfo $directory)
    {
        if (!$directory->isDir()) {
            throw new \InvalidArgumentException('This object must point to a directory.');
        }
    }

    #[\Override]
    public function isExists(): bool
    {
        return file_exists($this->getPathname());
    }

    #[\Override]
    public function isReadable(): bool
    {
        return $this->directory->isReadable();
    }

    #[\Override]
    public function isWritable(): bool
    {
        return $this->directory->isWritable();
    }

    #[\Override]
    public function getSize(): int|false
    {
        return $this->directory->getSize();
    }

    #[\Override]
    public function getPathname(): string
    {
        return $this->directory->getPathname();
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->directory->getPath();
    }

    #[\Override]
    public function getPerms(): int|false
    {
        return $this->directory->getPerms();
    }

    #[\Override]
    public function getOwner(): int|false
    {
        return $this->directory->getOwner();
    }

    #[\Override]
    public function getGroup(): int|false
    {
        return $this->directory->getGroup();
    }
}

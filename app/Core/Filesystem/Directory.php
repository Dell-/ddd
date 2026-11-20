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

    public function isExists(): bool
    {
        return file_exists($this->getPathname());
    }

    public function isReadable(): bool
    {
        return $this->directory->isReadable();
    }

    public function isWritable(): bool
    {
        return $this->directory->isWritable();
    }

    public function getSize(): int|false
    {
        return $this->directory->getSize();
    }

    public function getPathname(): string
    {
        return $this->directory->getPathname();
    }

    public function getPath(): string
    {
        return $this->directory->getPath();
    }

    public function getPerms(): int|false
    {
        return $this->directory->getPerms();
    }

    public function getOwner(): int|false
    {
        return $this->directory->getOwner();
    }

    public function getGroup(): int|false
    {
        return $this->directory->getGroup();
    }
}

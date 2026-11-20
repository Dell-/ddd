<?php

declare(strict_types=1);

namespace Core\Filesystem;

class File implements FileInterface
{
    public function __construct(private readonly \SplFileInfo $file)
    {
        if (!$file->isFile()) {
            throw new \InvalidArgumentException('This object must point to a file.');
        }
    }

    public function isExists(): bool
    {
        return file_exists($this->getPathname());
    }

    public function isReadable(): bool
    {
        return $this->file->isReadable();
    }

    public function isWritable(): bool
    {
        return $this->file->isWritable();
    }

    public function isExecutable(): bool
    {
        return $this->file->isExecutable();
    }

    public function getSize(): int|false
    {
        return $this->file->getSize();
    }

    public function getPathname(): string
    {
        return $this->file->getPathname();
    }

    public function getPath(): string
    {
        return $this->file->getPath();
    }

    public function getPerms(): int|false
    {
        return $this->file->getPerms();
    }

    public function getOwner(): int|false
    {
        return $this->file->getOwner();
    }

    public function getGroup(): int|false
    {
        return $this->file->getGroup();
    }

    public function getMTime(): int|false
    {
        return $this->file->getMTime();
    }

    public function getCTime(): int|false
    {
        return $this->file->getCTime();
    }

    public function getATime(): int|false
    {
        return $this->file->getATime();
    }

    public function getFilename(): string
    {
        return $this->file->getFilename();
    }

    public function getExtension(): string
    {
        return $this->file->getExtension();
    }
}

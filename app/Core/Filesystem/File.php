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

    #[\Override]
    public function isExists(): bool
    {
        return file_exists($this->getPathname());
    }

    #[\Override]
    public function isReadable(): bool
    {
        return $this->file->isReadable();
    }

    #[\Override]
    public function isWritable(): bool
    {
        return $this->file->isWritable();
    }

    #[\Override]
    public function isExecutable(): bool
    {
        return $this->file->isExecutable();
    }

    #[\Override]
    public function getSize(): int|false
    {
        return $this->file->getSize();
    }

    #[\Override]
    public function getPathname(): string
    {
        return $this->file->getPathname();
    }

    #[\Override]
    public function getPath(): string
    {
        return $this->file->getPath();
    }

    #[\Override]
    public function getPerms(): int|false
    {
        return $this->file->getPerms();
    }

    #[\Override]
    public function getOwner(): int|false
    {
        return $this->file->getOwner();
    }

    #[\Override]
    public function getGroup(): int|false
    {
        return $this->file->getGroup();
    }

    #[\Override]
    public function getMTime(): int|false
    {
        return $this->file->getMTime();
    }

    #[\Override]
    public function getCTime(): int|false
    {
        return $this->file->getCTime();
    }

    #[\Override]
    public function getATime(): int|false
    {
        return $this->file->getATime();
    }

    #[\Override]
    public function getFilename(): string
    {
        return $this->file->getFilename();
    }

    #[\Override]
    public function getExtension(): string
    {
        return $this->file->getExtension();
    }
}

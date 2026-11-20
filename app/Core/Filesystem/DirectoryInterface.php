<?php

declare(strict_types=1);

namespace Core\Filesystem;

interface DirectoryInterface
{
    public function isExists(): bool;

    public function isReadable(): bool;

    public function isWritable(): bool;

    public function getSize(): int|false;

    public function getPathname(): string;

    public function getPath(): string;

    public function getPerms(): int|false;

    public function getOwner(): int|false;

    public function getGroup(): int|false;
}

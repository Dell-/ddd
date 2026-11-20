<?php

declare(strict_types=1);

namespace Core\Helper;

class FilesystemHelper
{
    /**
     * Normalizes a path: unifies separators, removes "." and resolves "..".
     */
    public static function normalizePath(string $path, string $ds = DIRECTORY_SEPARATOR): string
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (!str_contains($ds . $path, "{$ds}.") && !str_contains($path, "{$ds}{$ds}")) {
            return $path;
        }

        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && $parts !== [] && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || ($part === '' && $parts !== [])) {
                continue;
            } else {
                $parts[] = $part;
            }
        }

        $path = implode($ds, $parts);

        return $path === '' ? '.' : $path;
    }

    public static function createDirectory(string $path, int $mode = 0775, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }

        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode, true);
        }

        if (!mkdir($path, $mode) && !is_dir($path)) {
            return false;
        }

        return chmod($path, $mode);
    }

    public static function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst) && !static::createDirectory($dst)) {
            throw new \RuntimeException('Unable to create directory: ' . $dst);
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new \RuntimeException('Unable to open directory: ' . $src);
        }

        try {
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $from = $src . DIRECTORY_SEPARATOR . $file;
                $to = $dst . DIRECTORY_SEPARATOR . $file;
                if (is_file($from)) {
                    copy($from, $to);
                } else {
                    static::copyDirectory($from, $to);
                }
            }
        } finally {
            closedir($handle);
        }
    }
}

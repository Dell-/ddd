<?php
namespace Core\Helper;

/**
 * Class FilesystemHelper
 */
class FilesystemHelper
{
    /**
     * Normalizes a file/directory path
     *
     * @param string $path
     * @param string $ds
     * @return string
     */
    public static function normalizePath($path, $ds = DS)
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        $parts = [];
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } else if ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    /**
     * Creates a new directory
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public static function createDirectory($path, $mode = 0775, $recursive = true)
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        if ($recursive && !is_dir($parentDir)) {
            static::createDirectory($parentDir, $mode, true);
        }
        $result = mkdir($path, $mode);
        chmod($path, $mode);

        return $result;
    }

    /**
     * Copies a whole directory as another one
     *
     * @param string $src
     * @param string $dst
     * @throws \Exception
     */
    public static function copyDirectory($src, $dst)
    {
        if (!is_dir($dst)) {
            static::createDirectory($dst, 0775, true);
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new \Exception('Unable to open directory: ' . $src);
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DS . $file;
            $to = $dst . DS . $file;
            if (is_file($from)) {
                copy($from, $to);
            } else {
                static::copyDirectory($from, $to);
            }
        }
        closedir($handle);
    }
}

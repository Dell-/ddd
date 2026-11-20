<?php

declare(strict_types=1);

namespace Core\Helper;

class StringHelper
{
    public const HASH_VER = 'v-0.0.0.1';

    /**
     * Short (non-cryptographic) hash of the string.
     */
    public static function hash(string $string): string
    {
        return sprintf('%x', crc32($string . self::HASH_VER));
    }

    /**
     * Length of the string in bytes.
     */
    public static function byteLength(string $string): int
    {
        return strlen($string);
    }

    /**
     * Substring counted in bytes.
     */
    public static function byteSubstr(string $string, int $start, ?int $length = null): string
    {
        return substr($string, $start, $length);
    }
}

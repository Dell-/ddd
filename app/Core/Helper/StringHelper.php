<?php
namespace Core\Helper;

/**
 * Class StringHelper
 */
class StringHelper
{
    /**
     * Generate a CRC32 hash for the input string
     *
     * @param string $string
     * @return string
     */
    public static function hash($string)
    {
        return sprintf('%x', crc32($string . \Bootstrap::getVersion()));
    }

    /**
     * Returns the number of bytes in the given string
     *
     * @param string $string
     * @return int
     */
    public static function byteLength($string)
    {
        return mb_strlen($string, '8bit');
    }

    /**
     * Returns the portion of string specified by the start and length parameters
     *
     * @param string $string
     * @param int $start
     * @param int $length
     * @return string
     */
    public static function byteSubstr($string, $start, $length = null)
    {
        return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
    }
}

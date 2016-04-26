<?php
namespace Core\Filesystem;

/**
 * Interface FilterInterface
 */
interface FilterInterface
{
    /**
     * @param string $pattern
     */
    public function setPattern($pattern);

    /**
     * @param string $name
     */
    public function setName($name);

    /**
     * @param string $mask
     */
    public function setPathMask($mask);
}

<?php
namespace Core\Config;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    /**
     * @return \DOMDocument
     */
    public function read();
}

<?php
namespace Core\Config;

/**
 * Interface ReaderInterface
 */
interface ReaderInterface
{
    const ATTRIBUTES = '@attributes';

    const VALUE = '@value';

    /**
     * @return array
     */
    public function read();
}

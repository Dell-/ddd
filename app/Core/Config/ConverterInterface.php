<?php
namespace Core\Config;

use Core\Config\ReaderInterface;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface
{
    /**
     * @param ReaderInterface $reader
     * @return array
     */
    public function convert(ReaderInterface $reader);
}

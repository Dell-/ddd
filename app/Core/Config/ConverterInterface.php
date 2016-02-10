<?php
namespace Core\Config;

/**
 * Interface ConverterInterface
 */
interface ConverterInterface
{
    /**
     * @param \DOMDocument $document
     * @return array
     */
    public function convert(\DOMDocument $document);
}

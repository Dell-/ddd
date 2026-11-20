<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Config\ReaderInterface;

/**
 * Config reader that returns a document built from an XML string.
 */
final class StringReader implements ReaderInterface
{
    public function __construct(private readonly string $xml)
    {
    }

    public function read(): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadXML($this->xml);

        return $document;
    }
}

<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

class XPathFactory
{
    public function create(\DOMDocument $document): \DOMXPath
    {
        return new \DOMXPath($document);
    }
}

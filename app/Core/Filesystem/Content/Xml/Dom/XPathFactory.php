<?php
namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Class XPathFactory
 */
class XPathFactory
{
    /**
     * Create new object DOMXPath for DOM document
     *
     * @param \DOMDocument $document
     * @return \DOMXPath
     */
    public function create(\DOMDocument $document)
    {
        return new \DOMXPath($document);
    }
}

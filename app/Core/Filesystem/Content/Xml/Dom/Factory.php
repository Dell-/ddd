<?php
namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Class Factory
 */
class Factory
{
    const XML_VERSION = '1.0';

    const XML_ENCODING = 'UTF-8';

    /**
     * Create class XML document
     *
     * @param string $xmlContent
     * @return \DOMDocument
     */
    public function create($xmlContent)
    {
        $domDocument = new \DOMDocument(static::XML_VERSION, static::XML_ENCODING);
        $domDocument->loadXML($xmlContent);

        return $domDocument;
    }
}

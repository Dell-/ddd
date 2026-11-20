<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

class Factory
{
    public const string XML_VERSION = '1.0';
    public const string XML_ENCODING = 'UTF-8';

    public function create(string $xmlContent): \DOMDocument
    {
        if (trim($xmlContent) === '') {
            throw new \InvalidArgumentException('XML document cannot be empty.');
        }

        $document = new \DOMDocument(static::XML_VERSION, static::XML_ENCODING);
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->loadXML($xmlContent)) {
                $error = libxml_get_last_error();
                throw new \InvalidArgumentException(
                    'Invalid XML document: ' . ($error !== false ? trim($error->message) : 'unknown error')
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }
}

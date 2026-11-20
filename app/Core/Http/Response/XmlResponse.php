<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Status;
use Core\Http\View\XmlEncoder;

/**
 * XML response (RFC 7303). Accepts an XML string, a DOM document, a SimpleXML element or data,
 * which is encoded with XmlEncoder.
 */
class XmlResponse extends Response
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        mixed $xml,
        Status|int $status = Status::Ok,
        array $headers = [],
        string $mediaType = 'application/xml',
    ) {
        $content = match (true) {
            is_string($xml) => $xml,
            $xml instanceof \DOMDocument => (string) $xml->saveXML(),
            $xml instanceof \SimpleXMLElement => (string) $xml->asXML(),
            default => new XmlEncoder()->encode($xml),
        };

        parent::__construct($status, ['Content-Type' => $mediaType] + $headers, $content);
    }
}

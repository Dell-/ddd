<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * XML (RFC 7303): application/xml, text/xml and "+xml" types; the parsed body is a SimpleXMLElement.
 *
 * Document type declarations are rejected: they enable XXE and entity expansion attacks
 * (OWASP XML External Entity Prevention); network access is disabled.
 */
final class XmlParser implements BodyParserInterface
{
    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return $mediaType->isXml();
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        $body = (string) $request->getBody();
        if (trim($body) === '') {
            return new ParsedBody(null);
        }
        if (preg_match('/<!DOCTYPE/i', $body)) {
            throw new BadRequestException('XML document type declarations are not allowed.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
            if ($xml === false) {
                $error = libxml_get_last_error();
                throw new BadRequestException('Invalid XML body: ' . ($error !== false ? trim($error->message) : 'parse error') . '.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return new ParsedBody($xml);
    }
}

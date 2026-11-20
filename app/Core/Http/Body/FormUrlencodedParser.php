<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * application/x-www-form-urlencoded for any method (PHP parses it only for POST).
 */
final class FormUrlencodedParser implements BodyParserInterface
{
    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return $mediaType->essence === 'application/x-www-form-urlencoded';
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        return new ParsedBody(FormData::parseUrlencoded((string) $request->getBody()));
    }
}

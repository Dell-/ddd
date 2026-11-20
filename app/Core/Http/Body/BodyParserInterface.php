<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\HttpException;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Parses a request body of the supported media types. Register parsers in BodyParsingMiddleware (di.xml).
 */
interface BodyParserInterface
{
    public function supports(MediaType $mediaType): bool;

    /**
     * @throws HttpException 400 for a malformed body, 413 for exceeded limits
     */
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody;
}

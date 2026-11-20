<?php

declare(strict_types=1);

namespace Core\Http\Middleware;

use Core\Http\Body\BodyParserInterface;
use Core\Http\Body\FormUrlencodedParser;
use Core\Http\Body\JsonLinesParser;
use Core\Http\Body\JsonParser;
use Core\Http\Body\JsonSeqParser;
use Core\Http\Body\MultipartParser;
use Core\Http\Body\XmlParser;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parses the body by its Content-Type with the first parser that supports it and sets the parsed body
 * and uploaded files. A body PHP has already parsed (form/multipart POST) is kept.
 * Unknown media types are left unparsed: the action reads getBody() / getBodyAsFile().
 */
final readonly class BodyParsingMiddleware implements MiddlewareInterface
{
    /**
     * @param list<BodyParserInterface> $parsers
     */
    public function __construct(
        private array $parsers = [
            new JsonParser(),
            new JsonLinesParser(),
            new JsonSeqParser(),
            new FormUrlencodedParser(),
            new MultipartParser(),
            new XmlParser(),
        ],
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $mediaType = MediaType::parse($request->getHeaderLine('Content-Type'));
        $alreadyParsed = $request->getParsedBody() !== null || $request->getUploadedFiles() !== [];

        if ($mediaType !== null && !$alreadyParsed) {
            $parser = array_find($this->parsers, static fn (BodyParserInterface $parser): bool => $parser->supports($mediaType));
            if ($parser !== null) {
                $parsed = $parser->parse($request, $mediaType);
                $request = $request->withParsedBody($parsed->data);
                if ($parsed->files !== []) {
                    $request = $request->withUploadedFiles($parsed->files);
                }
            }
        }

        return $handler->handle($request);
    }
}

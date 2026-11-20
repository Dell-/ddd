<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON Text Sequences, application/json-seq (RFC 7464): every JSON text is preceded by RS (0x1E)
 * and followed by LF. The parsed body is the list of values. Malformed records are rejected (400):
 * RFC 7464 §2.3 lets parsers decide, an API should not silently drop data.
 */
final readonly class JsonSeqParser implements BodyParserInterface
{
    public const string MEDIA_TYPE = 'application/json-seq';

    private const string RS = "\x1E";

    public function __construct(
        private int $maxRecords = 100000,
        private int $depth = 512,
    ) {
    }

    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return $mediaType->essence === self::MEDIA_TYPE;
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        $body = (string) $request->getBody();
        if (trim($body) === '') {
            return new ParsedBody([]);
        }
        if ($body[0] !== self::RS) {
            throw new BadRequestException('A JSON text sequence must start with the RS (0x1E) character.');
        }

        $records = [];
        foreach (array_slice(explode(self::RS, $body), 1) as $index => $text) {
            if (trim($text) === '') {
                continue; // RFC 7464 §2.1: empty records are allowed
            }
            if (count($records) >= $this->maxRecords) {
                throw new ContentTooLargeException(sprintf('At most %d records are allowed.', $this->maxRecords));
            }
            $records[] = JsonParser::decode($text, $this->depth, sprintf('JSON text %d', $index + 1));
        }

        return new ParsedBody($records);
    }
}

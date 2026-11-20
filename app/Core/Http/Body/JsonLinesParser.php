<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Newline delimited JSON: JSON Lines (jsonlines.org) and NDJSON (github.com/ndjson/ndjson-spec).
 * One JSON text per line ("\n" or "\r\n"), empty lines are ignored. The parsed body is the list of values.
 *
 * Media types in use: application/jsonl, application/x-ndjson, application/x-jsonlines,
 * application/jsonlines, application/x-jsonl.
 */
final readonly class JsonLinesParser implements BodyParserInterface
{
    public const array MEDIA_TYPES = [
        'application/jsonl',
        'application/x-ndjson',
        'application/x-jsonlines',
        'application/jsonlines',
        'application/x-jsonl',
    ];

    public function __construct(
        private int $maxRecords = 100000,
        private int $depth = 512,
    ) {
    }

    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return in_array($mediaType->essence, self::MEDIA_TYPES, true);
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        $records = [];
        $body = $request->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }

        $line = 0;
        $buffer = '';
        while (true) {
            $chunk = $body->eof() ? '' : $body->read(65536);
            $buffer .= $chunk;
            $final = $chunk === '' && $body->eof();

            while (($position = strpos($buffer, "\n")) !== false || ($final && $buffer !== '')) {
                $text = $position === false ? $buffer : substr($buffer, 0, $position);
                $buffer = $position === false ? '' : substr($buffer, $position + 1);
                $line++;
                $text = rtrim($text, "\r");
                if (trim($text) === '') {
                    continue;
                }
                if (count($records) >= $this->maxRecords) {
                    throw new ContentTooLargeException(sprintf('At most %d records are allowed.', $this->maxRecords));
                }
                $records[] = JsonParser::decode($text, $this->depth, sprintf('JSON on line %d', $line));
            }
            if ($final) {
                break;
            }
        }

        return new ParsedBody($records);
    }
}

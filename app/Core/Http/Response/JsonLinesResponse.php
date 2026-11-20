<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Message\Response;
use Core\Http\Message\Stream;
use Core\Http\Status;

/**
 * Newline delimited JSON (JSON Lines / NDJSON): one JSON text per line, for streaming record sets.
 * With $sequence = true the body is a JSON Text Sequence (RFC 7464, application/json-seq).
 */
class JsonLinesResponse extends Response
{
    /**
     * @param iterable<mixed> $records
     * @param array<string, string|list<string>> $headers
     * @throws \JsonException
     */
    public function __construct(
        iterable $records,
        Status|int $status = Status::Ok,
        array $headers = [],
        string $mediaType = 'application/x-ndjson',
        bool $sequence = false,
    ) {
        $stream = Stream::create();
        foreach ($records as $record) {
            $stream->write(($sequence ? "\x1E" : '') . json_encode($record, JsonResponse::DEFAULT_FLAGS) . "\n");
        }
        $stream->rewind();

        parent::__construct($status, ['Content-Type' => $sequence ? 'application/json-seq' : $mediaType] + $headers, $stream);
    }
}

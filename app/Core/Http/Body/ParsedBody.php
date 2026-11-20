<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Result of a body parser: the data for ServerRequest::withParsedBody() and the uploaded files.
 */
final readonly class ParsedBody
{
    /**
     * @param array<array-key, mixed>|object|null $data
     * @param array<array-key, mixed> $files tree of UploadedFileInterface
     */
    public function __construct(
        public array|object|null $data,
        public array $files = [],
    ) {
    }
}

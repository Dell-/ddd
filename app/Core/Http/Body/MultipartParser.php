<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\ContentDisposition;
use Core\Http\Header\HeaderValue;
use Core\Http\Header\MediaType;
use Core\Http\Message\Stream;
use Core\Http\Message\UploadedFile;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * multipart/form-data (RFC 7578, syntax of RFC 2046 §5.1.1), streamed: file parts go to temporary
 * streams, so large files are not kept in memory.
 *
 * PHP parses multipart only for POST; this parser makes PUT/PATCH uploads work the same way.
 * A part with a "filename" parameter is a file (UploadedFile), other parts are fields; names use
 * the bracket convention ("photos[]"), see FormData.
 */
final readonly class MultipartParser implements BodyParserInterface
{
    public function __construct(
        private int $maxParts = 1000,
        private int $maxFieldSize = 1048576,
        private int $maxHeaderSize = 8192,
    ) {
    }

    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return $mediaType->essence === 'multipart/form-data';
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        $boundary = $mediaType->parameter('boundary');
        // RFC 2046 §5.1.1: 1-70 characters, not ending with a space
        if ($boundary === null || !preg_match("/^[0-9A-Za-z'()+_,\\-.\\/:=? ]{0,69}[0-9A-Za-z'()+_,\\-.\\/:=?]$/", $boundary)) {
            throw new BadRequestException('multipart/form-data requires a valid boundary parameter.');
        }

        $reader = new MultipartReader($request->getBody(), $boundary);
        $fields = [];
        $files = [];
        $parts = 0;

        $reader->skipPreamble();
        while (!$reader->isFinished()) {
            if (++$parts > $this->maxParts) {
                throw new ContentTooLargeException(sprintf('At most %d parts are allowed.', $this->maxParts));
            }

            $headers = $reader->readHeaders($this->maxHeaderSize);
            $disposition = ContentDisposition::parse($headers['content-disposition'] ?? '');
            if ($disposition === null || $disposition->type !== 'form-data' || $disposition->name === null) {
                throw new BadRequestException('Every part needs Content-Disposition: form-data; name="…".');
            }

            if ($disposition->parameter('filename') !== null) {
                $stream = Stream::create();
                $size = $reader->readBody($stream);
                $stream->rewind();
                $filename = $disposition->filename;
                $empty = $size === 0 && ($filename === null || $filename === '');
                FormData::add($files, $disposition->name, new UploadedFile(
                    $stream,
                    $size,
                    $empty ? UPLOAD_ERR_NO_FILE : UPLOAD_ERR_OK,
                    $filename === '' ? null : $filename,
                    isset($headers['content-type']) ? MediaType::parse($headers['content-type'])?->essence : null
                ));
            } else {
                $value = $reader->readBodyAsString($this->maxFieldSize);
                FormData::add($fields, $disposition->name, $value);
            }
        }

        return new ParsedBody($fields, $files);
    }
}

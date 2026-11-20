<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\HeaderValue;
use Core\Http\Message\Stream;
use Psr\Http\Message\StreamInterface;

/**
 * @internal streaming reader of multipart bodies for MultipartParser
 */
final class MultipartReader
{
    private string $buffer = "\r\n"; // the first delimiter is not preceded by CRLF

    private bool $finished = false;

    private readonly string $delimiter;

    public function __construct(private readonly StreamInterface $stream, string $boundary)
    {
        $this->delimiter = "\r\n--" . $boundary;
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function skipPreamble(): void
    {
        $this->readUntilDelimiter(null);
    }

    /**
     * @return array<string, string> lower-case names
     */
    public function readHeaders(int $maxSize): array
    {
        $this->fill(2);
        if (str_starts_with($this->buffer, "\r\n")) {
            $this->buffer = substr($this->buffer, 2); // part without headers

            return [];
        }

        while (($end = strpos($this->buffer, "\r\n\r\n")) === false) {
            if (strlen($this->buffer) > $maxSize) {
                throw new ContentTooLargeException('Multipart part headers are too large.');
            }
            if (!$this->readChunk()) {
                throw new BadRequestException('Unexpected end of the multipart body in part headers.');
            }
        }
        if ($end > $maxSize) {
            throw new ContentTooLargeException('Multipart part headers are too large.');
        }

        $headers = [];
        foreach (explode("\r\n", substr($this->buffer, 0, $end)) as $line) {
            $colon = strpos($line, ':');
            if ($colon === false || !HeaderValue::isToken(substr($line, 0, $colon))) {
                throw new BadRequestException('Invalid multipart part header.');
            }
            $headers[strtolower(substr($line, 0, $colon))] = trim(substr($line, $colon + 1));
        }
        $this->buffer = substr($this->buffer, $end + 4);

        return $headers;
    }

    /**
     * Copies the part body into the stream and returns its size.
     */
    public function readBody(StreamInterface $target): int
    {
        return $this->readUntilDelimiter($target);
    }

    public function readBodyAsString(int $maxSize): string
    {
        $target = Stream::create();
        $size = $this->readUntilDelimiter($target, $maxSize);
        $target->rewind();

        return $size === 0 ? '' : $target->getContents();
    }

    private function readUntilDelimiter(?StreamInterface $target, ?int $maxSize = null): int
    {
        $size = 0;
        $keep = strlen($this->delimiter) - 1;

        while (true) {
            $position = strpos($this->buffer, $this->delimiter);
            if ($position !== false) {
                $size += $this->write($target, substr($this->buffer, 0, $position), $maxSize, $size);
                $this->buffer = substr($this->buffer, $position + strlen($this->delimiter));
                $this->afterDelimiter();

                return $size;
            }

            // everything except a possible start of the delimiter is content
            if (strlen($this->buffer) > $keep) {
                $size += $this->write($target, substr($this->buffer, 0, -$keep), $maxSize, $size);
                $this->buffer = substr($this->buffer, -$keep);
            }
            if (!$this->readChunk()) {
                throw new BadRequestException('Unexpected end of the multipart body: the closing delimiter is missing.');
            }
        }
    }

    /**
     * After "--boundary": "--" closes the body, otherwise optional whitespace and CRLF start a part.
     */
    private function afterDelimiter(): void
    {
        $this->fill(2);
        if (str_starts_with($this->buffer, '--')) {
            $this->finished = true;
            $this->buffer = '';

            return;
        }

        while (true) {
            $this->buffer = ltrim($this->buffer, " \t");
            if ($this->buffer === '' && $this->readChunk()) {
                continue;
            }
            $this->fill(2);
            break;
        }
        if (!str_starts_with($this->buffer, "\r\n")) {
            throw new BadRequestException('Invalid multipart delimiter line.');
        }
        $this->buffer = substr($this->buffer, 2);
    }

    private function write(?StreamInterface $target, string $data, ?int $maxSize, int $written): int
    {
        if ($data === '') {
            return 0;
        }
        if ($maxSize !== null && $written + strlen($data) > $maxSize) {
            throw new ContentTooLargeException(sprintf('A form field exceeds %d bytes.', $maxSize));
        }
        $target?->write($data);

        return strlen($data);
    }

    private function fill(int $length): void
    {
        while (strlen($this->buffer) < $length && $this->readChunk()) {
        }
    }

    private function readChunk(): bool
    {
        if ($this->stream->eof()) {
            return false;
        }
        $chunk = $this->stream->read(65536);
        $this->buffer .= $chunk;

        return $chunk !== '' || !$this->stream->eof();
    }
}

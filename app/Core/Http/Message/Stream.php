<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 stream over a PHP stream resource.
 */
final class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $resource;

    private ?int $size = null;

    private bool $seekable;

    private bool $readable;

    private bool $writable;

    /**
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new \InvalidArgumentException('A stream resource is required.');
        }

        $this->resource = $resource;
        $meta = stream_get_meta_data($resource);
        $mode = $meta['mode'];
        $this->seekable = $meta['seekable'];
        $this->readable = strpbrk($mode, 'r+') !== false;
        $this->writable = strpbrk($mode, 'waxc+') !== false;
    }

    /**
     * In-memory stream (spills to a temporary file over 2 MB) with the given content, positioned at the start.
     */
    public static function create(string $content = ''): self
    {
        $resource = fopen('php://temp', 'r+b');
        if ($resource === false) {
            throw new \RuntimeException('Cannot open php://temp.');
        }
        if ($content !== '') {
            fwrite($resource, $content);
            rewind($resource);
        }

        return new self($resource);
    }

    public static function fromFile(string $filename, string $mode = 'rb'): self
    {
        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new \RuntimeException(sprintf('Cannot open "%s".', $filename));
        }

        return new self($resource);
    }

    public function __destruct()
    {
        $this->close();
    }

    #[\Override]
    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    #[\Override]
    public function close(): void
    {
        if ($this->resource !== null) {
            $resource = $this->detach();
            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    #[\Override]
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->size = null;
        $this->seekable = $this->readable = $this->writable = false;

        return $resource;
    }

    #[\Override]
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }
        if ($this->resource === null) {
            return null;
        }

        $uri = stream_get_meta_data($this->resource)['uri'] ?? '';
        if (is_string($uri) && $uri !== '' && !str_starts_with($uri, 'php://')) {
            clearstatcache(true, $uri);
        }
        $stats = fstat($this->resource);
        if ($stats === false || !isset($stats['size'])) {
            return null;
        }
        // the size of write streams changes, cache only for read-only streams
        if (!$this->writable) {
            $this->size = $stats['size'];
        }

        return $stats['size'];
    }

    #[\Override]
    public function tell(): int
    {
        $position = ftell($this->attached());
        if ($position === false) {
            throw new \RuntimeException('Cannot determine the stream position.');
        }

        return $position;
    }

    #[\Override]
    public function eof(): bool
    {
        return $this->resource === null || feof($this->resource);
    }

    #[\Override]
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    #[\Override]
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('The stream is not seekable.');
        }
        if (fseek($this->attached(), $offset, $whence) === -1) {
            throw new \RuntimeException(sprintf('Cannot seek to position %d.', $offset));
        }
    }

    #[\Override]
    public function rewind(): void
    {
        $this->seek(0);
    }

    #[\Override]
    public function isWritable(): bool
    {
        return $this->writable;
    }

    #[\Override]
    public function write(string $string): int
    {
        if (!$this->writable) {
            throw new \RuntimeException('The stream is not writable.');
        }
        $this->size = null;
        $result = fwrite($this->attached(), $string);
        if ($result === false) {
            throw new \RuntimeException('Cannot write to the stream.');
        }

        return $result;
    }

    #[\Override]
    public function isReadable(): bool
    {
        return $this->readable;
    }

    #[\Override]
    public function read(int $length): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('The stream is not readable.');
        }
        if ($length < 0) {
            throw new \InvalidArgumentException('Length must not be negative.');
        }
        if ($length === 0) {
            return '';
        }
        $result = fread($this->attached(), $length);
        if ($result === false) {
            throw new \RuntimeException('Cannot read from the stream.');
        }

        return $result;
    }

    #[\Override]
    public function getContents(): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('The stream is not readable.');
        }
        $contents = stream_get_contents($this->attached());
        if ($contents === false) {
            throw new \RuntimeException('Cannot read the stream contents.');
        }

        return $contents;
    }

    #[\Override]
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->resource === null) {
            return $key === null ? [] : null;
        }
        $meta = stream_get_meta_data($this->resource);

        return $key === null ? $meta : ($meta[$key] ?? null);
    }

    /**
     * @return resource
     */
    private function attached()
    {
        return $this->resource ?? throw new \RuntimeException('The stream is detached.');
    }
}

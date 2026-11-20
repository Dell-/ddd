<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Header\ContentDisposition;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * PSR-7 uploaded file with a friendly API.
 *
 * The same class represents every way a file reaches the server: a multipart/form-data part
 * (parsed by PHP or by the framework for PUT/PATCH), a raw request body (REST upload),
 * a data URI (RFC 2397) or a base64 string from a JSON document.
 *
 *     $file = $request->getUploadedFile('avatar');
 *     if (!$file?->isOk()) { ... $file?->getErrorMessage() ... }
 *     $file->getMediaType();          // detected from the content, not the client's claim
 *     $path = $file->store('/var/uploads');   // safe random name with the extension
 */
class UploadedFile implements UploadedFileInterface
{
    private const array ERROR_MESSAGES = [
        UPLOAD_ERR_OK => 'The file was uploaded.',
        UPLOAD_ERR_INI_SIZE => 'The file exceeds the upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE => 'The file exceeds the MAX_FILE_SIZE of the form.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write the file to disk.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
    ];

    private ?string $file = null;

    private ?StreamInterface $stream = null;

    private bool $moved = false;

    private ?string $detectedMediaType = null;

    /**
     * @param StreamInterface|string $streamOrFile stream with the content or the path of a file (e.g. $_FILES tmp_name)
     */
    public function __construct(
        StreamInterface|string $streamOrFile,
        private readonly ?int $size = null,
        private readonly int $error = UPLOAD_ERR_OK,
        private readonly ?string $clientFilename = null,
        private readonly ?string $clientMediaType = null,
    ) {
        if (!isset(self::ERROR_MESSAGES[$error])) {
            throw new \InvalidArgumentException(sprintf('Invalid upload error code %d.', $error));
        }
        if (is_string($streamOrFile)) {
            $this->file = $streamOrFile;
        } else {
            $this->stream = $streamOrFile;
        }
    }

    public static function fromString(string $content, ?string $clientFilename = null, ?string $clientMediaType = null): self
    {
        return new self(Stream::create($content), strlen($content), UPLOAD_ERR_OK, $clientFilename, $clientMediaType);
    }

    /**
     * File from a data URI (RFC 2397): "data:image/png;base64,iVBORw0…".
     */
    public static function fromDataUri(string $dataUri, ?string $clientFilename = null): self
    {
        if (!preg_match('#^data:([^,]*?)(;base64)?,(.*)$#s', trim($dataUri), $matches)) {
            throw new \InvalidArgumentException('Invalid data URI.');
        }
        $content = $matches[2] !== ''
            ? base64_decode($matches[3], true)
            : rawurldecode($matches[3]);
        if ($content === false) {
            throw new \InvalidArgumentException('Invalid base64 data in the data URI.');
        }
        $mediaType = $matches[1] !== '' ? $matches[1] : 'text/plain;charset=US-ASCII';

        return self::fromString($content, $clientFilename, $mediaType);
    }

    /**
     * File from a base64 string (RFC 4648 §4, also accepts the URL-safe alphabet of §5).
     */
    public static function fromBase64(string $base64, ?string $clientFilename = null, ?string $clientMediaType = null): self
    {
        $content = base64_decode(strtr(trim($base64), '-_', '+/'), true);
        if ($content === false) {
            throw new \InvalidArgumentException('Invalid base64 data.');
        }

        return self::fromString($content, $clientFilename, $clientMediaType);
    }

    #[\Override]
    public function getStream(): StreamInterface
    {
        $this->assertAvailable();

        return $this->stream ??= Stream::fromFile((string) $this->file);
    }

    #[\Override]
    public function moveTo(string $targetPath): void
    {
        $this->assertAvailable();
        if ($targetPath === '') {
            throw new \InvalidArgumentException('The target path must not be empty.');
        }
        $this->getMediaType(); // detected before the move, so it stays available afterwards

        if ($this->file !== null) {
            $moved = PHP_SAPI !== 'cli' && is_uploaded_file($this->file)
                ? move_uploaded_file($this->file, $targetPath)
                : rename($this->file, $targetPath);
            if (!$moved) {
                throw new \RuntimeException(sprintf('Cannot move the uploaded file to "%s".', $targetPath));
            }
        } else {
            $target = Stream::fromFile($targetPath, 'wb');
            $source = $this->getStream();
            if ($source->isSeekable()) {
                $source->rewind();
            }
            while (!$source->eof()) {
                $target->write($source->read(1048576));
            }
            $target->close();
        }

        $this->moved = true;
    }

    #[\Override]
    public function getSize(): ?int
    {
        return $this->size ?? $this->stream?->getSize() ?? ($this->file !== null ? (filesize($this->file) ?: null) : null);
    }

    #[\Override]
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * The name sent by the client. Untrusted: use getSafeClientFilename() or store() for file system names.
     */
    #[\Override]
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * The media type sent by the client. Untrusted: use getMediaType() for decisions.
     */
    #[\Override]
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    public function isOk(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getErrorMessage(): string
    {
        return self::ERROR_MESSAGES[$this->error];
    }

    public function isMoved(): bool
    {
        return $this->moved;
    }

    /**
     * Client file name without directories and control characters ("" if there is none).
     */
    public function getSafeClientFilename(): string
    {
        return ContentDisposition::sanitizeFilename((string) $this->clientFilename);
    }

    /**
     * Lower-case extension of the client file name ("" if there is none or it is not alphanumeric).
     */
    public function getClientExtension(): string
    {
        $extension = strtolower(pathinfo($this->getSafeClientFilename(), PATHINFO_EXTENSION));

        return preg_match('/^[a-z0-9]{1,16}$/', $extension) ? $extension : '';
    }

    /**
     * Media type detected from the content (libmagic), e.g. "image/png"; also available after a move.
     */
    public function getMediaType(): string
    {
        if ($this->detectedMediaType === null) {
            if (!$this->isOk()) {
                return 'application/octet-stream';
            }
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if ($this->file !== null && !$this->moved) {
                $type = $finfo->file($this->file);
            } else {
                $stream = $this->getStream();
                if ($stream->isSeekable()) {
                    $stream->rewind();
                }
                $type = $finfo->buffer($stream->read(8192));
                if ($stream->isSeekable()) {
                    $stream->rewind();
                }
            }
            $this->detectedMediaType = $type ?: 'application/octet-stream';
        }

        return $this->detectedMediaType;
    }

    /**
     * Whether the detected media type matches one of the patterns ("image/png", "image/*").
     */
    public function hasMediaType(string ...$patterns): bool
    {
        $type = $this->getMediaType();
        foreach ($patterns as $pattern) {
            if ($pattern === $type || (str_ends_with($pattern, '/*') && str_starts_with($type, substr($pattern, 0, -1)))) {
                return true;
            }
        }

        return false;
    }

    public function getContents(): string
    {
        $stream = $this->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        return $stream->getContents();
    }

    public function getHash(string $algorithm = 'sha256'): string
    {
        $context = hash_init($algorithm);
        $stream = $this->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        while (!$stream->eof()) {
            hash_update($context, $stream->read(1048576));
        }

        return hash_final($context);
    }

    /**
     * Moves the file into the directory and returns its path.
     *
     * Without $filename a random name with the client extension is used ("3f2a….png"), so client
     * names never reach the file system. An existing file is never overwritten.
     */
    public function store(string $directory, ?string $filename = null): string
    {
        if (!$this->isOk()) {
            throw new \RuntimeException('Cannot store a failed upload: ' . $this->getErrorMessage());
        }
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Cannot create the directory "%s".', $directory));
        }

        if ($filename === null) {
            $extension = $this->getClientExtension();
            $filename = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');
        } else {
            $filename = ContentDisposition::sanitizeFilename($filename);
            if ($filename === '') {
                throw new \InvalidArgumentException('Invalid file name.');
            }
        }

        $path = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) {
            throw new \RuntimeException(sprintf('The file "%s" already exists.', $path));
        }
        $this->moveTo($path);

        return $path;
    }

    private function assertAvailable(): void
    {
        if (!$this->isOk()) {
            throw new \RuntimeException('The upload failed: ' . $this->getErrorMessage());
        }
        if ($this->moved) {
            throw new \RuntimeException('The uploaded file has already been moved.');
        }
    }
}

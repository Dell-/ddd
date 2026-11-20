<?php

declare(strict_types=1);

namespace Core\Http\Response;

use Core\Http\Exception\PreconditionFailedException;
use Core\Http\Exception\RangeNotSatisfiableException;
use Core\Http\Header\ContentDisposition;
use Core\Http\Header\DispositionType;
use Core\Http\Message\Response;
use Core\Http\Message\Stream;
use Core\Http\Status;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * File download or inline display.
 *
 * Sends Content-Type (detected), Content-Length, Content-Disposition (RFC 6266), Last-Modified,
 * a strong ETag and Accept-Ranges. prepare() evaluates the preconditions of RFC 9110 §13.2.2
 * (If-Match, If-Unmodified-Since, If-None-Match, If-Modified-Since: 412 / 304) and a single byte
 * range (RFC 9110 §14, If-Range): 206 Partial Content or 416.
 *
 *     return new FileResponse('/var/reports/2026.pdf', 'Звіт 2026.pdf');
 *     return new FileResponse($path, disposition: DispositionType::Inline);
 */
class FileResponse extends Response implements PreparableResponseInterface
{
    private readonly string $path;

    private readonly int $fileSize;

    private readonly int $modified;

    public function __construct(
        string $path,
        ?string $filename = null,
        DispositionType $disposition = DispositionType::Attachment,
        ?string $mediaType = null,
    ) {
        if (!is_file($path) || !is_readable($path)) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a readable file.', $path));
        }
        $this->path = $path;
        $this->fileSize = (int) filesize($path);
        $this->modified = (int) filemtime($path);

        $mediaType ??= (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
        if (str_starts_with($mediaType, 'text/') && !str_contains($mediaType, 'charset')) {
            $mediaType .= '; charset=utf-8';
        }

        parent::__construct(Status::Ok, [
            'Content-Type' => $mediaType,
            'Content-Length' => (string) $this->fileSize,
            'Content-Disposition' => (string) ContentDisposition::create($disposition, $filename ?? basename($path)),
            'Last-Modified' => self::httpDate($this->modified),
            'ETag' => '"' . hash('xxh128', $this->fileSize . ':' . $this->modified . ':' . fileinode($path)) . '"',
            'Accept-Ranges' => 'bytes',
        ], Stream::fromFile($path));
    }

    #[\Override]
    public function prepare(ServerRequestInterface $request): ResponseInterface
    {
        $etag = $this->getHeaderLine('ETag');
        $method = $request->getMethod();

        // RFC 9110 §13.2.2 step 1-2
        if ($request->hasHeader('If-Match')) {
            if (!self::etagMatches($request->getHeaderLine('If-Match'), $etag, false)) {
                throw new PreconditionFailedException();
            }
        } elseif ($request->hasHeader('If-Unmodified-Since')) {
            $since = self::parseDate($request->getHeaderLine('If-Unmodified-Since'));
            if ($since !== null && $this->modified > $since) {
                throw new PreconditionFailedException();
            }
        }

        // step 3-4
        if ($request->hasHeader('If-None-Match')) {
            if (self::etagMatches($request->getHeaderLine('If-None-Match'), $etag, true)) {
                if ($method === 'GET' || $method === 'HEAD') {
                    return $this->notModified();
                }
                throw new PreconditionFailedException();
            }
        } elseif (($method === 'GET' || $method === 'HEAD') && $request->hasHeader('If-Modified-Since')) {
            $since = self::parseDate($request->getHeaderLine('If-Modified-Since'));
            if ($since !== null && $this->modified <= $since) {
                return $this->notModified();
            }
        }

        // step 5: Range (only for GET)
        if ($method === 'GET' && $request->hasHeader('Range') && $this->ifRangeMatches($request)) {
            return $this->range($request->getHeaderLine('Range'));
        }

        return $this;
    }

    private function range(string $header): ResponseInterface
    {
        if (!preg_match('/^bytes=\s*(\d*)\s*-\s*(\d*)\s*$/i', $header, $m) || ($m[1] === '' && $m[2] === '')) {
            return $this; // multiple or unknown ranges: ignore (RFC 9110 §14.2 allows it)
        }
        if ($this->fileSize === 0) {
            throw new RangeNotSatisfiableException($this->fileSize);
        }

        if ($m[1] === '') {
            $length = min((int) $m[2], $this->fileSize); // suffix range: last N bytes
            if ($length === 0) {
                throw new RangeNotSatisfiableException($this->fileSize);
            }
            [$start, $end] = [$this->fileSize - $length, $this->fileSize - 1];
        } else {
            $start = (int) $m[1];
            $end = $m[2] === '' ? $this->fileSize - 1 : min((int) $m[2], $this->fileSize - 1);
            if ($start >= $this->fileSize || $start > $end) {
                throw new RangeNotSatisfiableException($this->fileSize);
            }
        }

        $length = $end - $start + 1;
        $source = fopen($this->path, 'rb');
        $target = Stream::create();
        if ($source !== false) {
            fseek($source, $start);
            $remaining = $length;
            while ($remaining > 0 && !feof($source)) {
                $chunk = (string) fread($source, min(1048576, $remaining));
                $target->write($chunk);
                $remaining -= strlen($chunk);
            }
            fclose($source);
        }
        $target->rewind();

        return $this->withStatus(Status::PartialContent)
            ->withHeader('Content-Range', sprintf('bytes %d-%d/%d', $start, $end, $this->fileSize))
            ->withHeader('Content-Length', (string) $length)
            ->withBody($target);
    }

    /**
     * If-Range (RFC 9110 §13.1.5): send the range only if the representation is unchanged.
     */
    private function ifRangeMatches(ServerRequestInterface $request): bool
    {
        if (!$request->hasHeader('If-Range')) {
            return true;
        }
        $value = trim($request->getHeaderLine('If-Range'));
        if (str_starts_with($value, '"')) {
            return $value === $this->getHeaderLine('ETag');
        }
        $date = self::parseDate($value);

        return $date !== null && $date === $this->modified;
    }

    private function notModified(): ResponseInterface
    {
        // RFC 9110 §15.4.5: the 304 carries the validators, no content
        return new Response(Status::NotModified, [
            'ETag' => $this->getHeaderLine('ETag'),
            'Last-Modified' => $this->getHeaderLine('Last-Modified'),
        ]);
    }

    /**
     * Strong comparison for If-Match, weak for If-None-Match (RFC 9110 §8.8.3.2).
     */
    private static function etagMatches(string $header, string $etag, bool $weak): bool
    {
        if (trim($header) === '*') {
            return true;
        }
        $normalize = static fn (string $tag): string => $weak ? (string) preg_replace('#^W/#', '', trim($tag)) : trim($tag);

        return array_any(
            explode(',', $header),
            static fn (string $candidate): bool => ($weak || !str_starts_with(trim($candidate), 'W/'))
                && $normalize($candidate) === $normalize($etag)
        );
    }

    /**
     * HTTP-date (RFC 9110 §5.6.7), including the obsolete formats recipients must accept.
     */
    private static function parseDate(string $value): ?int
    {
        foreach (['D, d M Y H:i:s \G\M\T', 'l, d-M-y H:i:s \G\M\T', 'D M j H:i:s Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, trim($value), new \DateTimeZone('UTC'));
            if ($date !== false) {
                return $date->getTimestamp();
            }
        }

        return null;
    }

    private static function httpDate(int $timestamp): string
    {
        return gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
    }
}

<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-17 factories of the framework's PSR-7 implementation.
 */
final class HttpFactory implements
    RequestFactoryInterface,
    ResponseFactoryInterface,
    ServerRequestFactoryInterface,
    StreamFactoryInterface,
    UploadedFileFactoryInterface,
    UriFactoryInterface
{
    #[\Override]
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    #[\Override]
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, reasonPhrase: $reasonPhrase !== '' ? $reasonPhrase : null);
    }

    #[\Override]
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequest
    {
        return new ServerRequest($method, $uri, $serverParams);
    }

    #[\Override]
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    #[\Override]
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if (!preg_match('/^[rwaxc]\+?[bt]?$/', $mode)) {
            throw new \InvalidArgumentException(sprintf('Invalid stream mode "%s".', $mode));
        }

        return Stream::fromFile($filename, $mode);
    }

    #[\Override]
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }

    #[\Override]
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null,
    ): UploadedFileInterface {
        return new UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
    }

    #[\Override]
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}

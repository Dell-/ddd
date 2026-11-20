<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Header\Accept;
use Core\Http\Header\MediaType;
use Core\Http\Method;

/**
 * PSR-7 server request with the conveniences of the framework.
 * Everything is derived from the PSR-7 state, so with*() methods keep the helpers consistent.
 */
interface ServerRequestInterface extends \Psr\Http\Message\ServerRequestInterface
{
    /** Attribute with the name of the matched route */
    public const string ATTRIBUTE_ROUTE = '_route';

    /** Attribute with all path parameters of the matched route */
    public const string ATTRIBUTE_ROUTE_PARAMS = '_route_params';

    public function getMethodEnum(): ?Method;

    public function isMethod(Method|string $method): bool;

    /**
     * Value from the query string; dots address nested values ("filter.status").
     */
    public function getQueryParam(string $name, mixed $default = null): mixed;

    /**
     * Value from the parsed body (array or object); dots address nested values.
     */
    public function getBodyParam(string $name, mixed $default = null): mixed;

    public function getCookieParam(string $name, ?string $default = null): ?string;

    /**
     * Input value: route parameter, then body, then query string.
     */
    public function input(string $name, mixed $default = null): mixed;

    public function getRouteName(): ?string;

    /**
     * @return array<string, string>
     */
    public function getRouteParams(): array;

    /**
     * Uploaded file by field name: "avatar", "photos.0", "photos[0]", "documents.passport".
     */
    public function getUploadedFile(string $name): ?UploadedFile;

    /**
     * All files of a field ("photos[]" uploads) as a flat list; one file gives a list of one.
     *
     * @return list<UploadedFile>
     */
    public function getUploadedFileList(string $name): array;

    /**
     * The raw body as a file (REST upload: PUT /files/report.pdf with Content-Type: application/pdf).
     * The client file name comes from Content-Disposition (RFC 6266), if sent.
     */
    public function getBodyAsFile(?string $defaultFilename = null): UploadedFile;

    /**
     * Content-Type of the body, null without a (valid) one.
     */
    public function getMediaType(): ?MediaType;

    public function getContentLength(): ?int;

    public function getAccept(): Accept;

    /**
     * Whether the client accepts the media type (Accept header, RFC 9110 §12.5.1).
     */
    public function accepts(string $mediaType): bool;

    /**
     * https (as seen by the server).
     */
    public function isSecure(): bool;

    /**
     * "scheme://host[:port]" of the request (RFC 6454 serialization).
     */
    public function getOrigin(): string;
}

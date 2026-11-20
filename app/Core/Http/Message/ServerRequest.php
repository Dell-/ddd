<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Header\Accept;
use Core\Http\Header\ContentDisposition;
use Core\Http\Header\MediaType;
use Core\Http\Method;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 server request. Create it with ServerRequestFactory::fromGlobals() or HttpFactory.
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * @var array<string, string>
     */
    private array $cookieParams = [];

    /**
     * @var array<array-key, mixed>
     */
    private array $queryParams = [];

    /**
     * @var array<array-key, mixed>|object|null
     */
    private array|object|null $parsedBody = null;

    /**
     * @var array<array-key, mixed> tree of UploadedFileInterface
     */
    private array $uploadedFiles = [];

    /**
     * @param array<string, mixed> $serverParams
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        private readonly array $serverParams = [],
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
    ) {
        parent::__construct($method, $uri, $headers, $body, $protocolVersion);
    }

    /**
     * Wraps another PSR-7 implementation (e.g. a request replaced by third-party middleware).
     */
    public static function fromPsr(\Psr\Http\Message\ServerRequestInterface $request): self
    {
        if ($request instanceof self) {
            return $request;
        }

        $result = new self(
            $request->getMethod(),
            $request->getUri(),
            $request->getServerParams(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        );
        $result->attributes = $request->getAttributes();
        $result->cookieParams = $request->getCookieParams();
        $result->queryParams = $request->getQueryParams();
        $result->parsedBody = $request->getParsedBody();
        $result->uploadedFiles = $request->getUploadedFiles();

        return $result;
    }

    #[\Override]
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    #[\Override]
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    #[\Override]
    public function withCookieParams(array $cookies): static
    {
        $clone = clone $this;
        $clone->cookieParams = $cookies;

        return $clone;
    }

    #[\Override]
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    #[\Override]
    public function withQueryParams(array $query): static
    {
        $clone = clone $this;
        $clone->queryParams = $query;

        return $clone;
    }

    #[\Override]
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    #[\Override]
    public function withUploadedFiles(array $uploadedFiles): static
    {
        self::assertUploadedFiles($uploadedFiles);
        $clone = clone $this;
        $clone->uploadedFiles = $uploadedFiles;

        return $clone;
    }

    #[\Override]
    public function getParsedBody(): array|object|null
    {
        return $this->parsedBody;
    }

    #[\Override]
    public function withParsedBody($data): static
    {
        if ($data !== null && !is_array($data) && !is_object($data)) {
            throw new \InvalidArgumentException('Parsed body must be an array, an object or null.');
        }
        $clone = clone $this;
        $clone->parsedBody = $data;

        return $clone;
    }

    #[\Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    #[\Override]
    public function getAttribute(string $name, $default = null): mixed
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }

    #[\Override]
    public function withAttribute(string $name, $value): static
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;

        return $clone;
    }

    #[\Override]
    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);

        return $clone;
    }

    #[\Override]
    public function getMethodEnum(): ?Method
    {
        return Method::tryFrom($this->getMethod());
    }

    #[\Override]
    public function isMethod(Method|string $method): bool
    {
        return $this->getMethod() === ($method instanceof Method ? $method->value : strtoupper($method));
    }

    #[\Override]
    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return self::find($this->queryParams, $name) ?? $default;
    }

    #[\Override]
    public function getBodyParam(string $name, mixed $default = null): mixed
    {
        return self::find($this->parsedBody, $name) ?? $default;
    }

    #[\Override]
    public function getCookieParam(string $name, ?string $default = null): ?string
    {
        $value = $this->cookieParams[$name] ?? null;

        return is_string($value) ? $value : $default;
    }

    #[\Override]
    public function input(string $name, mixed $default = null): mixed
    {
        return $this->getRouteParams()[$name]
            ?? self::find($this->parsedBody, $name)
            ?? self::find($this->queryParams, $name)
            ?? $default;
    }

    #[\Override]
    public function getRouteName(): ?string
    {
        $name = $this->attributes[self::ATTRIBUTE_ROUTE] ?? null;

        return is_string($name) ? $name : null;
    }

    #[\Override]
    public function getRouteParams(): array
    {
        $params = $this->attributes[self::ATTRIBUTE_ROUTE_PARAMS] ?? [];

        return is_array($params) ? $params : [];
    }

    #[\Override]
    public function getUploadedFile(string $name): ?UploadedFile
    {
        $file = self::find($this->uploadedFiles, $name);
        if ($file instanceof UploadedFileInterface && !$file instanceof UploadedFile) {
            $file = new UploadedFile($file->getStream(), $file->getSize(), $file->getError(), $file->getClientFilename(), $file->getClientMediaType());
        }

        return $file instanceof UploadedFile ? $file : null;
    }

    #[\Override]
    public function getUploadedFileList(string $name): array
    {
        $files = self::find($this->uploadedFiles, $name);
        if ($files === null) {
            return [];
        }

        $files = is_array($files) ? $files : [$files];
        $result = [];
        array_walk_recursive(
            $files,
            static function (mixed $file) use (&$result): void {
                if ($file instanceof UploadedFileInterface) {
                    $result[] = $file instanceof UploadedFile
                        ? $file
                        : new UploadedFile($file->getStream(), $file->getSize(), $file->getError(), $file->getClientFilename(), $file->getClientMediaType());
                }
            }
        );

        return $result;
    }

    #[\Override]
    public function getBodyAsFile(?string $defaultFilename = null): UploadedFile
    {
        $disposition = $this->hasHeader('Content-Disposition')
            ? ContentDisposition::parse($this->getHeaderLine('Content-Disposition'))
            : null;
        $body = $this->getBody();

        return new UploadedFile(
            $body,
            $this->getContentLength() ?? $body->getSize(),
            UPLOAD_ERR_OK,
            $disposition?->filename ?? $defaultFilename,
            $this->getMediaType()?->essence
        );
    }

    #[\Override]
    public function getMediaType(): ?MediaType
    {
        return $this->hasHeader('Content-Type') ? MediaType::parse($this->getHeaderLine('Content-Type')) : null;
    }

    #[\Override]
    public function getContentLength(): ?int
    {
        $length = $this->getHeaderLine('Content-Length');

        return ctype_digit($length) ? (int) $length : null;
    }

    #[\Override]
    public function getAccept(): Accept
    {
        return Accept::parse($this->getHeaderLine('Accept'));
    }

    #[\Override]
    public function accepts(string $mediaType): bool
    {
        return $this->getAccept()->accepts($mediaType);
    }

    #[\Override]
    public function isSecure(): bool
    {
        return $this->getUri()->getScheme() === 'https';
    }

    #[\Override]
    public function getOrigin(): string
    {
        $uri = $this->getUri();
        $port = $uri->getPort();

        return $uri->getScheme() . '://' . $uri->getHost() . ($port !== null ? ':' . $port : '');
    }

    /**
     * Value by "a.b.c" or "a[b][c]" from nested arrays/objects.
     */
    private static function find(mixed $data, string $path): mixed
    {
        $keys = explode('.', str_replace(['][', '[', ']'], ['.', '.', ''], $path));
        foreach ($keys as $key) {
            if (is_array($data) && array_key_exists($key, $data)) {
                $data = $data[$key];
            } elseif (is_object($data) && !$data instanceof UploadedFileInterface && isset($data->{$key})) {
                $data = $data->{$key};
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * @param array<array-key, mixed> $files
     */
    private static function assertUploadedFiles(array $files): void
    {
        array_walk_recursive($files, static function (mixed $file): void {
            if (!$file instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException('Uploaded files must be a tree of UploadedFileInterface.');
            }
        });
    }
}

<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Header\HeaderValue;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * PSR-7 outgoing request.
 */
class Request implements RequestInterface
{
    use MessageTrait;

    private string $method;

    private UriInterface $uri;

    private ?string $requestTarget = null;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        string $method,
        UriInterface|string $uri,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
    ) {
        $this->method = $this->filterMethod($method);
        $this->uri = is_string($uri) ? new Uri($uri) : $uri;
        $this->setHeaders($headers);
        $this->protocolVersion = $protocolVersion;
        if ($body !== null) {
            $this->body = is_string($body) ? Stream::create($body) : $body;
        }
        if (!$this->hasHeader('Host') && $this->uri->getHost() !== '') {
            $this->headerNames = ['host' => 'Host'] + $this->headerNames;
            $this->headers = ['Host' => [$this->hostFromUri()]] + $this->headers;
        }
    }

    #[\Override]
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }
        $target = $this->uri->getPath() === '' ? '/' : $this->uri->getPath();
        if ($this->uri->getQuery() !== '') {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    #[\Override]
    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('/\s/', $requestTarget)) {
            throw new \InvalidArgumentException('Request target must not contain whitespace.');
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    #[\Override]
    public function getMethod(): string
    {
        return $this->method;
    }

    #[\Override]
    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->method = $this->filterMethod($method);

        return $clone;
    }

    #[\Override]
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    #[\Override]
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone = clone $this;
        $clone->uri = $uri;
        if ($uri->getHost() !== '' && (!$preserveHost || !$this->hasHeader('Host'))) {
            $clone->removeHeader('Host');
            $clone->headerNames = ['host' => 'Host'] + $clone->headerNames;
            $clone->headers = ['Host' => [$clone->hostFromUri()]] + $clone->headers;
        }

        return $clone;
    }

    /**
     * Method names are case-sensitive tokens (RFC 9110 §9.1): kept as given.
     */
    private function filterMethod(string $method): string
    {
        if (!HeaderValue::isToken($method)) {
            throw new \InvalidArgumentException(sprintf('Invalid HTTP method "%s".', $method));
        }

        return $method;
    }

    private function hostFromUri(): string
    {
        $port = $this->uri->getPort();

        return $this->uri->getHost() . ($port !== null ? ':' . $port : '');
    }
}

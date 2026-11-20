<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Header\HeaderValue;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 MessageInterface: protocol version, case-insensitive headers (RFC 9110 §5.1), body.
 */
trait MessageTrait
{
    private string $protocolVersion = '1.1';

    /**
     * @var array<string, list<string>> header name as given => values
     */
    private array $headers = [];

    /**
     * @var array<string, string> lower-case name => name as given
     */
    private array $headerNames = [];

    private ?StreamInterface $body = null;

    #[\Override]
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    #[\Override]
    public function withProtocolVersion(string $version): static
    {
        if (!preg_match('/^\d(\.\d)?$/', $version)) {
            throw new \InvalidArgumentException(sprintf('Invalid HTTP version "%s".', $version));
        }
        $clone = clone $this;
        $clone->protocolVersion = $version;

        return $clone;
    }

    /**
     * @return array<string, list<string>>
     */
    #[\Override]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[\Override]
    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    /**
     * @return list<string>
     */
    #[\Override]
    public function getHeader(string $name): array
    {
        $name = $this->headerNames[strtolower($name)] ?? null;

        return $name === null ? [] : $this->headers[$name];
    }

    #[\Override]
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    #[\Override]
    public function withHeader(string $name, $value): static
    {
        $values = $this->filterHeader($name, $value);
        $clone = clone $this;
        $existing = $clone->headerNames[strtolower($name)] ?? null;
        if ($existing === null) {
            $clone->headers[$name] = $values;
        } else {
            // keep the position of the replaced header
            $headers = [];
            foreach ($clone->headers as $key => $current) {
                $key === $existing ? $headers[$name] = $values : $headers[$key] = $current;
            }
            $clone->headers = $headers;
        }
        $clone->headerNames[strtolower($name)] = $name;

        return $clone;
    }

    #[\Override]
    public function withAddedHeader(string $name, $value): static
    {
        $values = $this->filterHeader($name, $value);
        $clone = clone $this;
        $existing = $clone->headerNames[strtolower($name)] ?? null;
        if ($existing === null) {
            $clone->headerNames[strtolower($name)] = $name;
            $clone->headers[$name] = $values;
        } else {
            $clone->headers[$existing] = [...$clone->headers[$existing], ...$values];
        }

        return $clone;
    }

    #[\Override]
    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        $clone->removeHeader($name);

        return $clone;
    }

    #[\Override]
    public function getBody(): StreamInterface
    {
        return $this->body ??= Stream::create();
    }

    #[\Override]
    public function withBody(StreamInterface $body): static
    {
        $clone = clone $this;
        $clone->body = $body;

        return $clone;
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function setHeaders(array $headers): void
    {
        foreach ($headers as $name => $value) {
            $name = (string) $name;
            $values = $this->filterHeader($name, $value);
            $existing = $this->headerNames[strtolower($name)] ?? null;
            if ($existing === null) {
                $this->headerNames[strtolower($name)] = $name;
                $this->headers[$name] = $values;
            } else {
                $this->headers[$existing] = [...$this->headers[$existing], ...$values];
            }
        }
    }

    private function removeHeader(string $name): void
    {
        $existing = $this->headerNames[strtolower($name)] ?? null;
        if ($existing !== null) {
            unset($this->headers[$existing], $this->headerNames[strtolower($name)]);
        }
    }

    /**
     * RFC 9110 §5.1 field name is a token, §5.5 field values must not contain CR, LF or NUL.
     *
     * @return list<string>
     */
    private function filterHeader(string $name, mixed $value): array
    {
        if (!HeaderValue::isToken($name)) {
            throw new \InvalidArgumentException(sprintf('Invalid header name "%s".', $name));
        }
        $values = is_array($value) ? array_values($value) : [$value];
        if ($values === []) {
            throw new \InvalidArgumentException(sprintf('Header "%s" needs a value.', $name));
        }

        $result = [];
        foreach ($values as $item) {
            if (!is_string($item) && !is_numeric($item)) {
                throw new \InvalidArgumentException(sprintf('Header "%s" value must be a string.', $name));
            }
            $item = trim((string) $item, " \t");
            if (preg_match('/[\r\n\0]/', $item)) {
                throw new \InvalidArgumentException(sprintf('Header "%s" value must not contain CR, LF or NUL.', $name));
            }
            $result[] = $item;
        }

        return $result;
    }
}

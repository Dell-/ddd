<?php

declare(strict_types=1);

namespace Core\Http\Message;

use Core\Http\Status;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 response. See Core\Http\Response for JSON, HTML, XML, file and other ready-made responses.
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    private int $statusCode;

    private string $reasonPhrase;

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        Status|int $status = Status::Ok,
        array $headers = [],
        StreamInterface|string|null $body = null,
        string $protocolVersion = '1.1',
        ?string $reasonPhrase = null,
    ) {
        $this->statusCode = $this->filterStatus($status);
        $this->reasonPhrase = $reasonPhrase ?? Status::reasonPhraseOf($this->statusCode);
        $this->setHeaders($headers);
        $this->protocolVersion = $protocolVersion;
        if ($body !== null) {
            $this->body = is_string($body) ? Stream::create($body) : $body;
        }
    }

    #[\Override]
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    #[\Override]
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param Status|int $code
     */
    #[\Override]
    public function withStatus($code, string $reasonPhrase = ''): static
    {
        $clone = clone $this;
        $clone->statusCode = $this->filterStatus($code);
        $clone->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : Status::reasonPhraseOf($clone->statusCode);

        return $clone;
    }

    private function filterStatus(mixed $status): int
    {
        if (!$status instanceof Status && !is_int($status)) {
            throw new \InvalidArgumentException('Status code must be an integer or a Status.');
        }
        $code = Status::code($status);
        if ($code < 100 || $code > 599) {
            throw new \InvalidArgumentException(sprintf('Invalid HTTP status code %d.', $code));
        }

        return $code;
    }
}

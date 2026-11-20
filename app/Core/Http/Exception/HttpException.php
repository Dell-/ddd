<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * An error that is sent to the client as an HTTP response.
 *
 * Carries the members of a problem details object (RFC 9457): type, title, status, detail and extensions,
 * and the headers required by the status code (for example Allow for 405, WWW-Authenticate for 401).
 * The detail is shown to the client, so it must not contain internal information.
 */
class HttpException extends \RuntimeException
{
    public const string TYPE_DEFAULT = 'about:blank';

    private readonly int $statusCode;

    /**
     * @var array<string, list<string>>
     */
    private array $headers = [];

    /**
     * @param array<string, string|list<string>> $headers
     * @param array<string, mixed> $extensions additional problem details members
     */
    public function __construct(
        Status|int $status,
        private readonly ?string $detail = null,
        array $headers = [],
        ?\Throwable $previous = null,
        private readonly string $type = self::TYPE_DEFAULT,
        private readonly ?string $title = null,
        private readonly array $extensions = [],
    ) {
        $this->statusCode = Status::code($status);
        if ($this->statusCode < 400 || $this->statusCode > 599) {
            throw new \InvalidArgumentException(sprintf('HTTP error status must be 4xx or 5xx, %d given.', $this->statusCode));
        }
        foreach ($headers as $name => $value) {
            $this->headers[$name] = array_values((array) $value);
        }

        parent::__construct($detail ?? $this->getTitle(), $this->statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * A URI reference that identifies the problem type ("about:blank" when the status code is enough).
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Short summary of the problem type; the reason phrase by default.
     */
    public function getTitle(): string
    {
        return $this->title ?? (Status::reasonPhraseOf($this->statusCode) ?: 'Error');
    }

    /**
     * Explanation specific to this occurrence of the problem, safe to show to the client.
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    /**
     * Headers to add to the error response.
     *
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}

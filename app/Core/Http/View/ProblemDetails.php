<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Exception\HttpException;
use Core\Http\Status;

/**
 * Problem details for HTTP APIs (RFC 9457).
 */
final readonly class ProblemDetails implements \JsonSerializable
{
    /**
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public int $status,
        public string $title,
        public string $type = HttpException::TYPE_DEFAULT,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $extensions = [],
    ) {
    }

    public static function fromException(HttpException $exception, ?string $instance = null): self
    {
        return new self(
            $exception->getStatusCode(),
            $exception->getTitle(),
            $exception->getType(),
            $exception->getDetail(),
            $instance,
            $exception->getExtensions()
        );
    }

    public static function fromStatus(Status $status, ?string $detail = null): self
    {
        return new self($status->value, $status->reasonPhrase(), detail: $detail);
    }

    /**
     * Members in RFC 9457 order; extensions cannot replace the standard members (§3.2).
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        $members = ['type' => $this->type, 'title' => $this->title, 'status' => $this->status];
        if ($this->detail !== null) {
            $members['detail'] = $this->detail;
        }
        if ($this->instance !== null) {
            $members['instance'] = $this->instance;
        }

        return $members + array_diff_key($this->extensions, $members + ['detail' => 1, 'instance' => 1]);
    }
}

<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Status;

/**
 * What an action wants to show, independent of the format. ViewRenderer picks the representation
 * (JSON, XML, HTML via a template, text, ...) by the Accept header (RFC 9110 §12).
 *
 *     return new View(['id' => 1, 'name' => 'Item'], template: 'item/show', status: Status::Ok);
 */
final readonly class View
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(
        public mixed $data = null,
        public ?string $template = null,
        public Status|int $status = Status::Ok,
        public array $headers = [],
    ) {
    }

    public function withStatus(Status|int $status): self
    {
        return new self($this->data, $this->template, $status, $this->headers);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->data, $this->template, $this->status, [$name => $value] + $this->headers);
    }

    /**
     * Data as an array for templates: arrays as they are, JsonSerializable/objects converted, scalars under "data".
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        $data = $this->data instanceof \JsonSerializable ? $this->data->jsonSerialize() : $this->data;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        return is_array($data) && array_all($data, static fn (mixed $value, int|string $key): bool => is_string($key)) ? $data : ['data' => $data];
    }
}

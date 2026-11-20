<?php

declare(strict_types=1);

namespace Core\Request;

final class Request implements RequestInterface
{
    /**
     * @param array<array-key, mixed> $data
     */
    public function __construct(
        private readonly string $method,
        private readonly string $route,
        private readonly array $data = [],
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function getAllData(): array
    {
        return $this->data;
    }
}

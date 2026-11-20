<?php

declare(strict_types=1);

namespace Core\Request;

interface RequestInterface
{
    public const METHOD_CLI = 'CLI';

    /**
     * HTTP method in upper case (GET, POST, ...) or "CLI" for console requests.
     */
    public function getMethod(): string;

    /**
     * URL path for HTTP requests, command name for console requests.
     */
    public function getRoute(): string;

    public function getData(string $key, mixed $default = null): mixed;

    /**
     * @return array<array-key, mixed>
     */
    public function getAllData(): array;
}

<?php

declare(strict_types=1);

namespace Core\Di;

/**
 * Definition of how an object is created: class, arguments, sharing and callbacks.
 */
class InstanceClass
{
    /**
     * @var list<array{method: string, arguments: array<string, mixed>}>
     */
    private array $callbacks = [];

    /**
     * @param array<string, mixed> $arguments
     */
    public function __construct(
        private readonly string $id,
        private readonly string $class,
        private readonly array $arguments = [],
        private readonly bool $shared = false,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function isShared(): bool
    {
        return $this->shared;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function addCallback(string $method, array $arguments = []): void
    {
        $this->callbacks[] = ['method' => $method, 'arguments' => $arguments];
    }

    /**
     * @return list<array{method: string, arguments: array<string, mixed>}>
     */
    public function getCallbacks(): array
    {
        return $this->callbacks;
    }
}

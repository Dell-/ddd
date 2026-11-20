<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

/**
 * DI test subject: arguments of all configurable types and a callback method.
 */
final class Service implements ServiceInterface
{
    /**
     * @var list<array{string, int}>
     */
    public array $calls = [];

    /**
     * @param array<array-key, mixed> $list
     */
    public function __construct(
        public readonly DependencyInterface $dependency,
        public readonly array $list = [],
        public readonly string $text = '',
        public readonly ?string $nullable = 'default',
        public readonly string $path = '',
    ) {
    }

    public function add(string $name, int $value = 1): void
    {
        $this->calls[] = [$name, $value];
    }

    public function withLeaf(Leaf $leaf): Leaf
    {
        return $leaf;
    }

    private function hidden(): void
    {
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

final class WithDefaults
{
    public function __construct(
        public readonly Leaf $leaf = new Leaf(1),
        public readonly string|int|bool|float $env = 'unset',
    ) {
    }
}

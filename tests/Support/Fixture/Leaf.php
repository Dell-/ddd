<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

final class Leaf
{
    public function __construct(public readonly int $number = 0)
    {
    }
}

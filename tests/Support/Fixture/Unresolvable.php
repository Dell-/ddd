<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

final class Unresolvable
{
    public function __construct(public readonly int $required)
    {
    }
}

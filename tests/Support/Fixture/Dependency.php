<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

final class Dependency implements DependencyInterface
{
    public function __construct(public readonly Leaf $leaf)
    {
    }
}

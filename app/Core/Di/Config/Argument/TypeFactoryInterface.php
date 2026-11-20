<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument;

interface TypeFactoryInterface
{
    public function create(string $type): TypeConverterInterface;

    /**
     * Converts a node that wraps exactly one typed value node
     * (for example <argument name="x"><int>1</int></argument>).
     *
     * @param array<string, mixed> $node
     */
    public function convertValue(array $node): mixed;
}

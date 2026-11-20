<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument;

interface TypeConverterInterface
{
    /**
     * @param array<string, mixed> $node
     */
    public function convert(array $node): mixed;
}

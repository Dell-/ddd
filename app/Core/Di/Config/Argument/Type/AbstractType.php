<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Argument\TypeFactoryInterface;
use Core\Di\Config\Xml\Converter;

abstract class AbstractType implements TypeConverterInterface
{
    public function __construct(protected readonly TypeFactoryInterface $typeFactory)
    {
    }

    /**
     * @param array<string, mixed> $node
     */
    protected function value(array $node): string
    {
        return trim((string) ($node[Converter::VALUE] ?? ''));
    }
}

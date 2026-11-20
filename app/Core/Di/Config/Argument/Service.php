<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument;

use Core\Di\Config\Xml\Converter;

/**
 * Converts <argument> nodes into values.
 */
class Service
{
    public function __construct(private readonly TypeFactoryInterface $typeFactory)
    {
    }

    /**
     * @param list<array<string, mixed>> $arguments
     * @return array<string, mixed>
     */
    public function createArguments(array $arguments): array
    {
        $result = [];
        foreach ($arguments as $argument) {
            $name = $argument[Converter::ATTRIBUTES]['name'];
            $result[$name] = $this->typeFactory->convertValue($argument);
        }

        return $result;
    }
}

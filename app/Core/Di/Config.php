<?php

declare(strict_types=1);

namespace Core\Di;

use Core\Config\ConverterInterface;
use Core\Config\ReaderInterface;

/**
 * DI configuration: interface preferences and instance definitions.
 */
class Config
{
    /**
     * @var array<string, InstanceClass>
     */
    private array $instances;

    public function __construct(ReaderInterface $reader, ConverterInterface $converter)
    {
        $this->instances = $converter->convert($reader->read());
    }

    public function getInstance(string $name): ?InstanceClass
    {
        return $this->instances[$name] ?? null;
    }

    public function hasInstance(string $name): bool
    {
        return isset($this->instances[$name]);
    }
}

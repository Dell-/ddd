<?php

declare(strict_types=1);

namespace Core\Route;

use Core\Config\ConverterInterface;
use Core\Config\ReaderInterface;

/**
 * Routes from routes.xml: method + name => action class.
 */
class Config
{
    /**
     * @var array<string, array<string, string>>|null
     */
    private ?array $routes = null;

    public function __construct(
        private readonly ReaderInterface $reader,
        private readonly ConverterInterface $converter,
    ) {
    }

    /**
     * Returns the action class of the route or null if there is no such route.
     */
    public function match(string $method, string $name): ?string
    {
        $this->routes ??= $this->converter->convert($this->reader->read());

        return $this->routes[strtoupper($method)][$name] ?? null;
    }
}

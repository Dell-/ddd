<?php

declare(strict_types=1);

namespace Core\Route;

use Core\Config\ConverterInterface;
use Core\Config\ReaderInterface;

/**
 * Routes and console commands from routes.xml (read on first use).
 */
class Config
{
    /**
     * @var array{routes: array<string, Route>, commands: array<string, string>}|null
     */
    private ?array $data = null;

    public function __construct(
        private readonly ReaderInterface $reader,
        private readonly ConverterInterface $converter,
    ) {
    }

    /**
     * HTTP routes in declaration order, keyed by name.
     *
     * @return array<string, Route>
     */
    public function getRoutes(): array
    {
        return $this->load()['routes'];
    }

    public function getRoute(string $name): ?Route
    {
        return $this->load()['routes'][$name] ?? null;
    }

    /**
     * Action class of the console command.
     */
    public function getCommand(string $name): ?string
    {
        return $this->load()['commands'][$name] ?? null;
    }

    /**
     * @return array{routes: array<string, Route>, commands: array<string, string>}
     */
    private function load(): array
    {
        if ($this->data === null) {
            /** @var array{routes: array<string, Route>, commands: array<string, string>} $data */
            $data = $this->converter->convert($this->reader->read()) + ['routes' => [], 'commands' => []];
            $this->data = $data;
        }

        return $this->data;
    }
}

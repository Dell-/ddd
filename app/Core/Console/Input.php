<?php

declare(strict_types=1);

namespace Core\Console;

/**
 * Console input: `bin/console <command> [arguments] [--flag] [--name=value]`.
 */
final readonly class Input
{
    /**
     * @param list<string> $arguments
     * @param array<string, string|true> $options
     */
    public function __construct(
        public string $command = '',
        public array $arguments = [],
        public array $options = [],
    ) {
    }

    /**
     * @param list<string> $argv including the script name
     */
    public static function fromArgv(array $argv): self
    {
        array_shift($argv);
        $command = (string) array_shift($argv);

        $arguments = [];
        $options = [];
        $onlyArguments = false;
        foreach ($argv as $value) {
            if (!$onlyArguments && $value === '--') {
                $onlyArguments = true;
            } elseif (!$onlyArguments && preg_match('/^--([\w-]+)(?:=(.*))?$/s', $value, $matches)) {
                $options[$matches[1]] = $matches[2] ?? true;
            } else {
                $arguments[] = $value;
            }
        }

        return new self($command, $arguments, $options);
    }

    public function getArgument(int $index, ?string $default = null): ?string
    {
        return $this->arguments[$index] ?? $default;
    }

    public function getOption(string $name, string|bool|null $default = null): string|bool|null
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }
}

<?php

declare(strict_types=1);

namespace Core\Console;

/**
 * Console output: normal output to STDOUT, errors to STDERR.
 */
final class Output
{
    /**
     * @param resource|null $stdout
     * @param resource|null $stderr
     */
    public function __construct(private $stdout = null, private $stderr = null)
    {
        $this->stdout ??= STDOUT;
        $this->stderr ??= STDERR;
    }

    public function write(string $text): void
    {
        fwrite($this->stdout, $text);
    }

    public function writeln(string $line = ''): void
    {
        $this->write($line . PHP_EOL);
    }

    public function error(string $line): void
    {
        fwrite($this->stderr, $line . PHP_EOL);
    }
}

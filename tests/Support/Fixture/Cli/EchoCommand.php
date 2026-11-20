<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Cli;

use Core\Console\CommandInterface;
use Core\Console\Input;
use Core\Console\Output;

/**
 * Prints "<command>:<name option>:<arguments>".
 */
final class EchoCommand implements CommandInterface
{
    #[\Override]
    public function run(Input $input, Output $output): int
    {
        $output->writeln(sprintf('%s:%s:%s', $input->command, $input->getOption('name', '-'), implode(',', $input->arguments)));

        return self::SUCCESS;
    }
}

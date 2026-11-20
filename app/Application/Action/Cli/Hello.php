<?php

declare(strict_types=1);

namespace Application\Action\Cli;

use Core\Console\CommandInterface;
use Core\Console\Input;
use Core\Console\Output;

/**
 * Example console command: `bin/console hello --name=World`.
 */
final class Hello implements CommandInterface
{
    #[\Override]
    public function run(Input $input, Output $output): int
    {
        $output->writeln(sprintf('Hello, %s!', $input->getOption('name', 'World')));

        return self::SUCCESS;
    }
}

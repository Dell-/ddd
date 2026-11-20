<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Cli;

use Core\Console\CommandInterface;
use Core\Console\Input;
use Core\Console\Output;

final class FailCommand implements CommandInterface
{
    #[\Override]
    public function run(Input $input, Output $output): int
    {
        throw new \RuntimeException('Command failed');
    }
}

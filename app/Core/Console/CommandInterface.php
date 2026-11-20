<?php

declare(strict_types=1);

namespace Core\Console;

/**
 * Console command, registered in routes.xml: <command name="hello" action="App\Cli\Hello"/>.
 */
interface CommandInterface
{
    public const int SUCCESS = 0;
    public const int FAILURE = 1;

    /**
     * @return int exit code
     */
    public function run(Input $input, Output $output): int;
}

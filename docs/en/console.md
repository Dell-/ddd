# Console

Commands are registered in `routes.xml` and run with `bin/console`:

```xml
<command name="hello" action="Application\Action\Cli\Hello"/>
<command name="cache:clear" action="App\Cli\ClearCache"/>
```

```sh
$ docker compose run --rm app php bin/console hello --name=World
Hello, World!
$ php bin/console nope; echo $?
Command "nope" not found.
1
```

## A command

```php
<?php

declare(strict_types=1);

namespace App\Cli;

use Core\Console\CommandInterface;
use Core\Console\Input;
use Core\Console\Output;

final class ImportItems implements CommandInterface
{
    public function __construct(private readonly ItemImporter $importer)   // autowired
    {
    }

    #[\Override]
    public function run(Input $input, Output $output): int
    {
        $file = $input->getArgument(0) ?? throw new \InvalidArgumentException('Usage: import <file> [--dry-run]');
        $count = $this->importer->import($file, dryRun: $input->hasOption('dry-run'));

        $output->writeln(sprintf('Imported %d items.', $count));

        return self::SUCCESS;
    }
}
```

## Input

`bin/console import items.csv --dry-run --limit=10 -- --not-an-option`

| | |
|---|---|
| `$input->command` | `"import"` |
| `$input->arguments`, `getArgument(0)` | `["items.csv", "--not-an-option"]` (everything after `--` is an argument) |
| `$input->options`, `getOption('limit')` | `["dry-run" => true, "limit" => "10"]` |
| `hasOption('dry-run')` | `true` |

## Output and exit codes

`$output->write()`, `writeln()` go to STDOUT, `error()` to STDERR. Return `CommandInterface::SUCCESS` (0)
or `FAILURE` (1). An exception prints `Class: message` to STDERR (the trace with `APP_DEBUG=1`), is logged
and exits with 1.

# Консоль

Команди реєструються в `routes.xml` і запускаються через `bin/console`:

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

## Команда

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

## Вхідні дані

`bin/console import items.csv --dry-run --limit=10 -- --not-an-option`

| | |
|---|---|
| `$input->command` | `"import"` |
| `$input->arguments`, `getArgument(0)` | `["items.csv", "--not-an-option"]` (усе після `--` є аргументом) |
| `$input->options`, `getOption('limit')` | `["dry-run" => true, "limit" => "10"]` |
| `hasOption('dry-run')` | `true` |

## Вивід і коди завершення

`$output->write()`, `writeln()` пишуть у STDOUT, `error()` — у STDERR. Повертайте `CommandInterface::SUCCESS` (0)
або `FAILURE` (1). Виняток виводить `Class: message` у STDERR (трасування — з `APP_DEBUG=1`), логується
і завершує роботу з кодом 1.

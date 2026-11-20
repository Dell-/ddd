<?php

declare(strict_types=1);

namespace Core\Request\Cli;

/**
 * Parses console arguments: `command --flag --name=value positional`.
 */
class Parser
{
    public const REQUEST_ROUTE = 'route';

    /**
     * @param list<string> $argv Arguments including the script name
     * @return array<array-key, mixed>
     */
    public function parse(array $argv): array
    {
        array_shift($argv); // script name

        $data = [self::REQUEST_ROUTE => array_shift($argv) ?? ''];
        foreach ($argv as $param) {
            if (preg_match('/^--([\w-]+)(?:=(.*))?$/s', $param, $matches)) {
                $data[$matches[1]] = $matches[2] ?? true;
            } else {
                $data[] = $param;
            }
        }

        return $data;
    }
}

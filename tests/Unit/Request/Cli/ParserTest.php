<?php

declare(strict_types=1);

namespace Tests\Unit\Request\Cli;

use Codeception\Test\Unit;
use Core\Request\Cli\Parser;
use Codeception\Attribute\DataProvider;

final class ParserTest extends Unit
{
    /**
     * @return iterable<string, array{list<string>, array<array-key, mixed>}>
     */
    public static function argvProvider(): iterable
    {
        yield 'no arguments' => [['console'], ['route' => '']];
        yield 'command only' => [['console', 'hello'], ['route' => 'hello']];
        yield 'flag, option, positional' => [
            ['console', 'hello', '--force', '--name=World', 'extra', '--dry-run=', '--eq=a=b'],
            ['route' => 'hello', 'force' => true, 'name' => 'World', 0 => 'extra', 'dry-run' => '', 'eq' => 'a=b'],
        ];
        yield 'single dash is positional' => [['console', 'x', '-v'], ['route' => 'x', 0 => '-v']];
    }

    /**
     * @param list<string> $argv
     * @param array<array-key, mixed> $expected
     */
    #[DataProvider('argvProvider')]
    public function testParse(array $argv, array $expected): void
    {
        $this->assertSame($expected, (new Parser())->parse($argv));
    }
}

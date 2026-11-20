<?php

declare(strict_types=1);

namespace Tests\Unit\Helper;

use Codeception\Test\Unit;
use Core\Helper\FilesystemHelper;
use Codeception\Attribute\DataProvider;

final class FilesystemHelperTest extends Unit
{
    /**
     * @return iterable<array{string, string}>
     */
    public static function pathProvider(): iterable
    {
        yield ['/a/b/c', '/a/b/c'];
        yield ['/a/b/', '/a/b'];
        yield ['a\\b', 'a/b'];
        yield ['/a/./b', '/a/b'];
        yield ['/a/b/../c', '/a/c'];
        yield ['/a//b', '/a/b'];
        yield ['../a', '../a'];
        yield ['a/..', '.'];
    }

    #[DataProvider('pathProvider')]
    public function testNormalizePath(string $path, string $expected): void
    {
        $this->assertSame($expected, FilesystemHelper::normalizePath($path, '/'));
    }
}

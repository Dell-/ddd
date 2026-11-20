<?php

declare(strict_types=1);

namespace Tests\Support;

use Core\Di\ContainerInterface;

/**
 * The application wired with the test configuration (tests/Support/Data/etc).
 */
final class TestApplication
{
    public static function configDirectory(): string
    {
        return __DIR__ . '/Data/etc';
    }

    public static function createContainer(): ContainerInterface
    {
        return \Bootstrap::createContainer(self::configDirectory());
    }
}

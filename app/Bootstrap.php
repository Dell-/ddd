<?php

declare(strict_types=1);

use Core\Service\ServiceInterface;

/**
 * Application start timestamp
 */
defined('BEGIN_TIME') or define('BEGIN_TIME', microtime(true));

/**
 * Application (app/) directory
 */
defined('BP') or define('BP', __DIR__);

/**
 * Short name of the directory separator
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

require_once BP . '/../vendor/autoload.php';

final class Bootstrap
{
    /**
     * Builds the DI container from Application/etc/di.xml and creates the application service.
     */
    public static function createApplication(): ServiceInterface
    {
        $container = new \Core\Di\Container(
            new \Core\Di\Config(
                new \Core\Config\Xml\Reader(
                    BP . '/Application/etc',
                    new \Core\Filesystem\Directory\Factory(),
                    new \Core\Filesystem\IteratorFactory(),
                    new \Core\Filesystem\File\Reader(),
                    new \Core\Filesystem\Content\Xml\Dom\MergerFactory(),
                    '#di\.xml$#'
                ),
                new \Core\Di\Config\Xml\Converter(
                    new \Core\Di\Config\Argument\Service(
                        new \Core\Di\Config\Argument\TypeFactory()
                    )
                )
            )
        );

        $service = $container->create(ServiceInterface::class);
        \assert($service instanceof ServiceInterface);

        return $service;
    }
}

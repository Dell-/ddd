<?php

declare(strict_types=1);

use Core\Di\ContainerInterface;
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

/**
 * Configuration directory (di.xml, routes.xml), can be changed with the APP_ETC_DIR environment variable
 */
defined('APP_ETC') or define('APP_ETC', rtrim(getenv('APP_ETC_DIR') ?: BP . '/Application/etc', '/\\'));

/**
 * Debug mode: error details in responses (APP_DEBUG environment variable)
 */
defined('APP_DEBUG') or define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL));

require_once BP . '/../vendor/autoload.php';

final class Bootstrap
{
    /**
     * Creates the application service from the configuration directory.
     */
    public static function createApplication(string $configDirectory = APP_ETC): ServiceInterface
    {
        $service = self::createContainer($configDirectory)->create(ServiceInterface::class);
        \assert($service instanceof ServiceInterface);

        return $service;
    }

    /**
     * Builds the DI container from di.xml files of the configuration directory.
     */
    public static function createContainer(string $configDirectory = APP_ETC): ContainerInterface
    {
        return new \Core\Di\Container(
            new \Core\Di\Config(
                new \Core\Config\Xml\Reader(
                    $configDirectory,
                    new \Core\Filesystem\Directory\Factory(),
                    new \Core\Filesystem\IteratorFactory(),
                    new \Core\Filesystem\File\Reader(),
                    new \Core\Filesystem\Content\Xml\Dom\MergerFactory(),
                    '#di\.xml$#',
                    BP . '/Core/Di/etc/di.xsd',
                    new \Core\Di\Config\Xml\MergeRules()
                ),
                new \Core\Di\Config\Xml\Converter(
                    new \Core\Di\Config\Argument\Service(
                        new \Core\Di\Config\Argument\TypeFactory()
                    )
                )
            )
        );
    }
}

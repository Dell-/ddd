<?php
/**
 * Gets the application start timestamp
 */
use Core\Service\ServiceInterface;

defined('BEGIN_TIME') or define('BEGIN_TIME', microtime(true));

/**
 * This constant defines the core directory
 */
defined('BP') or define('BP', __DIR__);

/**
 * This constant defines the short name delimiter directory
 */
defined('DS') or define('DS', DIRECTORY_SEPARATOR);

/**
 * Class Bootstrap
 */
class Bootstrap
{
    /**
     * Create application
     *
     * @return ServiceInterface
     */
    public static function createApplication()
    {
        $container = new \Core\Di\Container(
            new \Core\Di\Config(
                new \Core\Config\Xml\Reader(
                    BP . '/Application/etc',
                    new \Core\Filesystem\Directory\Factory(),
                    new \Core\Filesystem\IteratorFactory(),
                    new \Core\Filesystem\File\Reader(),
                    new \Core\Filesystem\Content\Xml\Dom\Merger()
                ),
                new \Core\Di\Config\Xml\Converter(
                    new \Core\Di\Config\Argument\Service(
                        new \Core\Di\Config\Argument\TypeFactory()
                    )
                )
            )
        );

        return $container->create(\Core\Service\ServiceInterface::class);
    }
}

require_once BP . '/../vendor/autoload.php';
require_once BP . '/Core/Autoloader.php';

spl_autoload_register([\Core\Autoloader::class, 'autoload'], true, true);

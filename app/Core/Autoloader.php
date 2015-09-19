<?php
namespace Core;

/**
 * Class Autoloader
 */
class Autoloader
{
    /**
     * Class autoload loader
     *
     * @param string $className
     * @throws \Exception
     */
    public static function autoload($className)
    {
        if (strpos($className, '\\') !== false) {
            $classFile = BP . DS . str_replace('\\', DS, $className) . '.php';
            if (!is_file($classFile)) {
                return;
            }
        } else {
            return;
        }

        include($classFile);

        if (!class_exists($className, false)
            && !interface_exists($className, false)
            && !trait_exists($className, false)
        ) {
            throw new \Exception("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }
}

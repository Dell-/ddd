<?php
namespace Core\Di;

use Core\Config\ReaderInterface;
use Core\Config\ConverterInterface;

/**
 * Class Container
 */
class Container implements ContainerInterface
{
    /**
     * Instances of classes
     *
     * @var InstanceClass[]
     */
    private $instances = [];

    /**
     * Shared instances of classes
     *
     * @var array
     */
    private $shared = [];

    /**
     * Constructor
     *
     * @param ReaderInterface $reader
     * @param ConverterInterface $converter
     */
    public function __construct(ReaderInterface $reader, ConverterInterface $converter)
    {
        $this->shared[$this->getClassName(get_class($this))] = $this;
        $this->instances = $converter->convert($reader);
    }

    /**
     * @inheritdoc
     */
    public function get($className)
    {
        $className = $this->getClassName($className);

        if (isset($this->instances[$className])) {
            $className = $this->instances[$className]->getClass();
        }

        if (isset($this->shared[$className])) {
            return $this->shared[$className];
        }

        $this->shared[$className] = $this->create($className);

        return $this->shared[$className];
    }

    /**
     * @inheritdoc
     */
    public function create($className, array $arguments = [])
    {
        $className = $this->getClassName($className);

        if (isset($this->instances[$className])) {
            $object = $this->createInstance(
                new \ReflectionClass($this->instances[$className]->getClass()),
                array_merge($this->compileObject($this->instances[$className]->getArguments()), $arguments)
            );
            if ($this->instances[$className]->isShared()) {
                $this->shared[$className] = $object;
            }

            return $object;
        }

        return $this->createInstance(new \ReflectionClass($className), $arguments);
    }

    /**
     * Compile the object
     *
     * @param array $arguments
     * @return array
     */
    private function compileObject(array $arguments)
    {
        foreach ($arguments as &$argument) {
            if ($argument instanceof InstanceClass) {
                $argument = $this->createInstance(
                    new \ReflectionClass($argument->getClass()),
                    $argument->getArguments()
                );
                continue;
            }

            if (is_array($argument)) {
                $argument = $this->compileObject($argument);
            }
        }
        unset($argument);

        return $arguments;
    }

    /**
     * Create object instance
     *
     * @param \ReflectionClass $classReflection
     * @param array $arguments
     * @return mixed
     */
    private function createInstance(\ReflectionClass $classReflection, array $arguments = [])
    {
        $class = $classReflection->getName();
        if ($classReflection->hasMethod('__construct')) {
            $method = $classReflection->getMethod('__construct');
            $arguments = $this->prepareArguments($method->getParameters(), $arguments);
        }

        return new $class(...array_values($arguments));
    }

    /**
     * Prepare ReflectionParameter array
     *
     * @param \ReflectionParameter[] $objectArguments
     * @param array $externalArguments
     * @return array
     */
    private function prepareArguments(array $objectArguments, array $externalArguments = [])
    {
        $creationArguments = [];
        foreach ($objectArguments as $argument) {
            $key = $argument->getName();
            if (isset($externalArguments[$key])) {
                $creationArguments[$key] = $externalArguments[$key];
                continue;
            }
            $isDefaultValue = $argument->isDefaultValueAvailable();
            $defaultValue = $isDefaultValue ? $argument->getDefaultValue() : null;

            $classReflection = $argument->getClass();
            if ($classReflection !== null && $isDefaultValue === false) {
                $value = $this->get($classReflection->getName());
            } else {
                $value = $defaultValue;
            }
            $creationArguments[$key] = $value;
        }

        return $creationArguments;
    }

    /**
     * Check the class name
     *
     * @param string $className
     * @return string
     * @throws \Exception
     */
    private function getClassName($className)
    {
        if (!is_string($className)) {
            throw new \Exception('Wrong type class name.');
        }

        return trim($className, '\\');
    }
}

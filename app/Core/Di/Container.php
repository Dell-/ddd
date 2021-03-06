<?php
namespace Core\Di;

/**
 * Class Container
 */
class Container implements ContainerInterface
{
    /**
     * Shared instances of classes
     *
     * @var array
     */
    private $shared = [];

    /**
     * Container configuration
     *
     * @var Config
     */
    private $config;

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->shared[$this->getClassName(get_class($this))] = $this;
    }

    /**
     * @inheritdoc
     */
    public function get($className)
    {
        $className = $this->getClassName($className);

        if ($this->config->hasInstance($className)) {
            $className = $this->config->getInstance($className)->getClass();
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

        if ($this->config->hasInstance($className)) {
            $instanceClass = $this->config->getInstance($className);
            $object = $this->createInstance(
                new \ReflectionClass($instanceClass->getClass()),
                array_merge($this->compileObjectArguments($instanceClass->getArguments()), $arguments)
            );

            foreach ($instanceClass->getCallbacks() as $callback) {
                $this->callMethod(
                    $object,
                    $callback['method'],
                    $this->compileObjectArguments($callback['arguments'])
                );
            }

            if ($instanceClass->isShared()) {
                $this->shared[$className] = $object;
            }

            return $object;
        }

        // Default - Object is not shared
        return $this->createInstance(new \ReflectionClass($className), $arguments);
    }

    /**
     * @inheritdoc
     */
    public function callMethod($object, $methodName, array $arguments = [])
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object argument must be Object type.');
        }

        $classReflection = new \ReflectionClass($object);
        if (!$classReflection->hasMethod($methodName)) {
            throw new \InvalidArgumentException(
                get_class($object) . '::' . $methodName . '() method does not exists.'
            );
        }

        $method = $classReflection->getMethod($methodName);
        if (!$method->isPublic()) {
            throw new \InvalidArgumentException(
                get_class($object) . '::' . $methodName . '() method must be public.'
            );
        }

        $arguments = $this->prepareArguments(
            $method->getParameters(),
            $arguments
        );

        return $object->$methodName(...array_values($arguments));
    }

    /**
     * Compile the object
     *
     * @param array $arguments
     * @return array
     */
    private function compileObjectArguments(array $arguments)
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
                $argument = $this->compileObjectArguments($argument);
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

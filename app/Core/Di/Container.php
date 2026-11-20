<?php

declare(strict_types=1);

namespace Core\Di;

/**
 * Reflection based dependency injection container configured by Config.
 */
class Container implements ContainerInterface
{
    /**
     * Shared instances keyed by class name.
     *
     * @var array<string, object>
     */
    private array $shared = [];

    public function __construct(private readonly Config $config)
    {
        $this->shared[static::class] = $this;
    }

    public function get(string $className): object
    {
        $className = $this->resolveClassName($className);

        return $this->shared[$className] ??= $this->create($className);
    }

    public function create(string $className, array $arguments = []): object
    {
        $className = $this->normalize($className);

        $instanceClass = $this->config->getInstance($className);
        if ($instanceClass === null) {
            // Not configured: the object is not shared
            return $this->createInstance($className, $arguments);
        }

        $object = $this->createInstance(
            $instanceClass->getClass(),
            array_merge($this->compileArguments($instanceClass->getArguments()), $arguments)
        );

        foreach ($instanceClass->getCallbacks() as $callback) {
            $this->callMethod($object, $callback['method'], $this->compileArguments($callback['arguments']));
        }

        if ($instanceClass->isShared()) {
            $this->shared[$instanceClass->getClass()] = $object;
        }

        return $object;
    }

    public function callMethod(object $object, string $methodName, array $arguments = []): mixed
    {
        $classReflection = new \ReflectionClass($object);
        if (!$classReflection->hasMethod($methodName)) {
            throw new \InvalidArgumentException($object::class . '::' . $methodName . '() method does not exist.');
        }

        $method = $classReflection->getMethod($methodName);
        if (!$method->isPublic()) {
            throw new \InvalidArgumentException($object::class . '::' . $methodName . '() method must be public.');
        }

        return $method->invokeArgs($object, $this->prepareArguments($method->getParameters(), $arguments));
    }

    /**
     * Turns configured object arguments (InstanceClass) into real objects, recursively.
     *
     * @param array<array-key, mixed> $arguments
     * @return array<array-key, mixed>
     */
    private function compileArguments(array $arguments): array
    {
        foreach ($arguments as &$argument) {
            if ($argument instanceof InstanceClass) {
                $argument = $this->createInstance(
                    $argument->getClass(),
                    $this->compileArguments($argument->getArguments())
                );
            } elseif (is_array($argument)) {
                $argument = $this->compileArguments($argument);
            }
        }
        unset($argument);

        return $arguments;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    private function createInstance(string $className, array $arguments = []): object
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException(
                sprintf('Cannot instantiate "%s": class does not exist or no preference is configured.', $className)
            );
        }

        $classReflection = new \ReflectionClass($className);
        if (!$classReflection->isInstantiable()) {
            throw new \InvalidArgumentException(sprintf('Class "%s" is not instantiable.', $className));
        }

        $constructor = $classReflection->getConstructor();
        if ($constructor === null) {
            return $classReflection->newInstance();
        }

        return $classReflection->newInstanceArgs($this->prepareArguments($constructor->getParameters(), $arguments));
    }

    /**
     * Builds the positional argument list: explicit arguments first, then defaults,
     * then objects resolved from the container by their declared class type.
     *
     * @param \ReflectionParameter[] $parameters
     * @param array<string, mixed> $arguments
     * @return list<mixed>
     */
    private function prepareArguments(array $parameters, array $arguments = []): array
    {
        $result = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $arguments)) {
                $result[] = $arguments[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $result[] = $parameter->getDefaultValue();
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $result[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->allowsNull()) {
                $result[] = null;
                continue;
            }

            throw new \InvalidArgumentException(sprintf(
                'Cannot resolve argument "$%s" of %s::%s().',
                $name,
                $parameter->getDeclaringClass()?->getName() ?? '',
                $parameter->getDeclaringFunction()->getName()
            ));
        }

        return $result;
    }

    /**
     * Maps an interface or class name to the class that is really instantiated.
     */
    private function resolveClassName(string $className): string
    {
        $className = $this->normalize($className);

        return $this->config->getInstance($className)?->getClass() ?? $className;
    }

    private function normalize(string $className): string
    {
        return trim($className, '\\');
    }
}

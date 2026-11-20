<?php

declare(strict_types=1);

namespace Core\Di;

/**
 * Dependency injection container.
 */
interface ContainerInterface
{
    /**
     * Returns a shared instance of the class (creates it on the first call).
     */
    public function get(string $className): object;

    /**
     * Creates a new instance of the class.
     *
     * @param array<string, mixed> $arguments Constructor arguments keyed by parameter name
     */
    public function create(string $className, array $arguments = []): object;

    /**
     * Calls a public method of the object, resolving missing arguments from the container.
     *
     * @param array<string, mixed> $arguments Method arguments keyed by parameter name
     */
    public function callMethod(object $object, string $methodName, array $arguments = []): mixed;
}

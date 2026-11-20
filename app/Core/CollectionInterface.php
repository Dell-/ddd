<?php

declare(strict_types=1);

namespace Core;

/**
 * @extends \IteratorAggregate<array-key, mixed>
 * @extends \ArrayAccess<array-key, mixed>
 */
interface CollectionInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    public function get(string|int $key, mixed $defaultValue = null): mixed;

    public function set(string|int $key, mixed $value): void;

    /**
     * Removes the value and returns it.
     */
    public function remove(string|int $key): mixed;

    public function removeAll(): void;

    public function has(string|int $key): bool;
}

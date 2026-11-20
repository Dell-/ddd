<?php

declare(strict_types=1);

namespace Core\Service;

use Core\Di\ContainerInterface;

/**
 * Placeholder of a service layer (not implemented yet).
 */
class Layer
{
    public function __construct(protected readonly ContainerInterface $container)
    {
    }

    public function collect(): void
    {
    }

    public function run(): void
    {
    }
}

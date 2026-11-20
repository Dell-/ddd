<?php

declare(strict_types=1);

namespace Core\Service;

interface ServiceInterface
{
    /**
     * Runs the application and returns the exit code (0 on success).
     */
    public function run(): int;
}

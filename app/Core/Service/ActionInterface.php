<?php

declare(strict_types=1);

namespace Core\Service;

interface ActionInterface
{
    /**
     * Executes the action and returns its output.
     */
    public function execute(): string;
}

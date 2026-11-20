<?php

declare(strict_types=1);

namespace Application\Action\Cli;

use Core\Request\Cli\Action;

/**
 * Example console action: `bin/console hello --name=World`.
 */
class Hello extends Action
{
    public function execute(): string
    {
        return sprintf('Hello, %s!', $this->request->getData('name', 'World'));
    }
}

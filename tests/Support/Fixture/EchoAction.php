<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

use Core\Request\Cli\Action;

/**
 * Returns "<route>:<value of the "name" parameter>".
 */
final class EchoAction extends Action
{
    public function execute(): string
    {
        return $this->request->getRoute() . ':' . $this->request->getData('name', '-');
    }
}

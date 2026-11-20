<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;

final class Fail extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): never
    {
        throw new \RuntimeException('Secret failure details');
    }
}

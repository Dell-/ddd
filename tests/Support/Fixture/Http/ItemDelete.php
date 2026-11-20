<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;

final class ItemDelete extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): null
    {
        return null;
    }
}

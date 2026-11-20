<?php

declare(strict_types=1);

namespace Application\Action\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;

final class Index extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): string
    {
        return static::class;
    }
}

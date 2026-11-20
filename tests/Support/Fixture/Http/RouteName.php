<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Tells which route served the request (X-Route header and text body).
 */
final class RouteName extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->text('served by ' . $request->getRouteName())->withHeader('X-Route', (string) $request->getRouteName());
    }
}

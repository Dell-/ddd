<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;

final class ItemUpdate extends AbstractAction
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function __invoke(ServerRequestInterface $request): array
    {
        return [
            'id' => (int) $request->getAttribute('id'),
            'method' => $request->getMethod(),
            'data' => $request->getParsedBody(),
        ];
    }
}

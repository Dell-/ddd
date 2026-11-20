<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;

/**
 * Plain data: negotiated as JSON or XML (no template, so no HTML).
 */
final class DataView extends AbstractAction
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function __invoke(ServerRequestInterface $request): array
    {
        return ['id' => 1, 'tags' => ['a', 'b'], 'active' => true, 'note' => null];
    }
}

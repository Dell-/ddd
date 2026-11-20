<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;

final class TemplateView extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): View
    {
        return $this->view(['name' => 'Templated <item>'], 'item');
    }
}

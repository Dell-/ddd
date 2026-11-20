<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Exception\NotFoundException;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;

/**
 * Items 1..100 exist; HTML is rendered with the "item" template.
 */
final class ItemShow extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): View
    {
        $id = (int) $request->getAttribute('id');
        if ($id < 1 || $id > 100) {
            throw new NotFoundException(sprintf('Item %d not found.', $id));
        }

        return $this->view(['id' => $id, 'name' => "Item $id"], 'item');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;
use Core\Route\UrlGeneratorInterface;

/**
 * Page of items; "next" is generated from the route config (page goes to the query string).
 */
final class ItemList extends AbstractAction
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    #[\Override]
    public function __invoke(ServerRequestInterface $request): View
    {
        $page = (int) $request->getQueryParam('page');
        $limit = (int) $request->getQueryParam('limit');
        $first = ($page - 1) * $limit + 1;
        $items = array_map(static fn (int $id): array => ['id' => $id, 'name' => "Item $id"], range($first, $first + $limit - 1));

        return $this->view([
            'items' => $items,
            'page' => $page,
            'limit' => $limit,
            'next' => $this->urls->generate('item.list', ['page' => $page + 1, 'limit' => $limit]),
        ]);
    }
}

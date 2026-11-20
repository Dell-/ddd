# Actions

An action handles the requests of a route. Two kinds are supported:

| Kind | Returns | Use for |
|---|---|---|
| `Core\Http\Action\ActionInterface` (`__invoke`) | anything: response, `View`, data, string, `null` | most actions |
| `Psr\Http\Server\RequestHandlerInterface` (`handle`) | a PSR-7 response | full control, PSR-15 interoperability |

## A typical action

```php
<?php

declare(strict_types=1);

namespace App\Item;

use Core\Http\Action\AbstractAction;
use Core\Http\Exception\NotFoundException;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;

final class ShowItem extends AbstractAction
{
    public function __construct(private readonly ItemRepository $items)    // autowired
    {
    }

    #[\Override]
    public function __invoke(ServerRequestInterface $request): View
    {
        $item = $this->items->find((int) $request->getAttribute('id'))
            ?? throw new NotFoundException('Item not found.');

        return $this->view($item, 'item/show');
    }
}
```

The action is created by the container for each request: constructor dependencies are autowired (see
[Dependency injection](../architecture/dependency-injection.md)), the request is the argument.

## Results

| `__invoke` returns | Response |
|---|---|
| `ResponseInterface` | sent as it is |
| `View` | negotiated: JSON, XML, HTML (with a template), text — see [content negotiation](content-negotiation.md) |
| array, `JsonSerializable`, object, scalar | wrapped in a `View` and negotiated |
| `string`, `Stringable` | HTML page (`text/html; charset=utf-8`) |
| `null` | `204 No Content` |
| exception | error response, see [Errors](errors.md) |

## AbstractAction helpers

```php
return $this->view($data, 'template', Status::Ok, ['X-Total' => '10']);  // negotiated
return $this->json($data, Status::Ok);
return $this->html('<p>Hi</p>');
return $this->text('pong');
return $this->xml($domDocument);                       // or a string, SimpleXMLElement, data
return $this->created('/items/42', $item);             // 201 + Location (+ JSON body)
return $this->accepted('/jobs/7');                     // 202 + Location: processed later
return $this->noContent();                             // 204
return $this->redirect('/items');                      // 303 See Other: after a form POST
return $this->redirect('/new', Status::MovedPermanently);
return $this->file('/var/reports/2026.pdf', 'Report 2026.pdf');
```

## A PSR-15 handler

```php
final class Health implements RequestHandlerInterface
{
    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse(['status' => 'ok']);
    }
}
```

The request passed to actions is always a `Core\Http\Message\ServerRequestInterface` (PSR-7 plus helpers);
`ServerRequest::fromPsr($request)` converts a request of another PSR-7 implementation.

## Console commands

Console commands are not actions: see [Console](../console.md).

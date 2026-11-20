# Actions

Action обробляє запити маршруту. Підтримуються два види:

| Вид | Повертає | Для чого |
|---|---|---|
| `Core\Http\Action\ActionInterface` (`__invoke`) | будь-що: відповідь, `View`, дані, рядок, `null` | більшість actions |
| `Psr\Http\Server\RequestHandlerInterface` (`handle`) | відповідь PSR-7 | повний контроль, сумісність із PSR-15 |

## Типовий action

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

Action створюється контейнером для кожного запиту: залежності конструктора підставляються автоматично (autowiring, див.
[Впровадження залежностей](../architecture/dependency-injection.md)), запит передається як аргумент.

## Результати

| `__invoke` повертає | Відповідь |
|---|---|
| `ResponseInterface` | надсилається як є |
| `View` | узгоджується: JSON, XML, HTML (із шаблоном), текст — див. [узгодження вмісту](content-negotiation.md) |
| масив, `JsonSerializable`, об'єкт, скаляр | обгортається у `View` і узгоджується |
| `string`, `Stringable` | HTML-сторінка (`text/html; charset=utf-8`) |
| `null` | `204 No Content` |
| виняток | відповідь із помилкою, див. [Помилки](errors.md) |

## Допоміжні методи AbstractAction

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

## Обробник PSR-15

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

Запит, що передається в actions, завжди є `Core\Http\Message\ServerRequestInterface` (PSR-7 плюс допоміжні методи);
`ServerRequest::fromPsr($request)` перетворює запит з іншої реалізації PSR-7.

## Консольні команди

Консольні команди не є actions: див. [Консоль](../console.md).

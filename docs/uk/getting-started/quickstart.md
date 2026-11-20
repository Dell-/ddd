# Швидкий старт

Створимо `GET /products/{id}`, який відповідає JSON (або XML, або HTML), і протестуємо його.

## 1. Action

`app/Application/Action/Http/ShowProduct.php`:

```php
<?php

declare(strict_types=1);

namespace Application\Action\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Exception\NotFoundException;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;

final class ShowProduct extends AbstractAction
{
    private const array PRODUCTS = [1 => 'Desk', 2 => 'Chair'];

    #[\Override]
    public function __invoke(ServerRequestInterface $request): View
    {
        $id = (int) $request->getAttribute('id');
        $name = self::PRODUCTS[$id] ?? throw new NotFoundException(sprintf('Product %d not found.', $id));

        return $this->view(['id' => $id, 'name' => $name]);
    }
}
```

Action отримує запит PSR-7 і повертає результат. `View` — це «дані у форматі, якому віддає перевагу клієнт»:
JSON, XML і — за наявності шаблону — HTML. Викидання `HttpException` породжує відповідь із помилкою.

## 2. Маршрут

`app/Application/etc/routes.xml`:

```xml
<routes trailing-slash="redirect" ...>
    <route name="home" method="GET" path="/" action="Application\Action\Http\Index"/>
    <route name="product.show" method="GET" path="/products/{id:\d+}" action="Application\Action\Http\ShowProduct"/>
    <command name="hello" action="Application\Action\Cli\Hello"/>
</routes>
```

## 3. Спробуйте

```sh
$ curl -i localhost:8080/products/1
HTTP/1.1 200 OK
Content-Type: application/json
Vary: Accept

{"id":1,"name":"Desk"}

$ curl -H 'Accept: application/xml' localhost:8080/products/1
<?xml version="1.0" encoding="UTF-8"?>
<response><id>1</id><name>Desk</name></response>

$ curl -i localhost:8080/products/9
HTTP/1.1 404 Not Found
Content-Type: application/problem+json

{"type":"about:blank","title":"Not Found","status":404,"detail":"Product 9 not found."}

$ curl -i localhost:8080/products/1/
HTTP/1.1 301 Moved Permanently
Location: /products/1
```

Останній приклад — це політика `trailing-slash="redirect"`: кожна сторінка має рівно один URL.

## 4. Тест

`tests/Unit/ShowProductTest.php` — action через справжні маршрутизатор і middleware:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use Codeception\Test\Unit;
use Core\Http\Kernel;
use Tests\Support\Requests;

final class ShowProductTest extends Unit
{
    public function testShow(): void
    {
        putenv('APP_SECRET=test-secret-0123456789abcdef0123456789');
        $kernel = \Bootstrap::createContainer()->get(Kernel::class);

        $response = $kernel->handle(Requests::make('GET', '/products/1'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"id":1,"name":"Desk"}', (string) $response->getBody());
    }
}
```

```sh
docker compose run --rm app vendor/bin/codecept run Unit ShowProductTest
```

## Наступні кроки

- [Маршрутизація](../http/routing.md): параметри, параметри запиту, генерація URL
- [Actions](../http/actions.md) і [запити](../http/requests.md)
- [Представлення та узгодження вмісту](../http/content-negotiation.md)

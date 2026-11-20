# Quick start

We build `GET /products/{id}` that answers JSON (or XML, or HTML) and test it.

## 1. The action

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

An action receives the PSR-7 request and returns a result. A `View` is "data in the format the client
prefers": JSON, XML and — with a template — HTML. Throwing an `HttpException` produces the error response.

## 2. The route

`app/Application/etc/routes.xml`:

```xml
<routes trailing-slash="redirect" ...>
    <route name="home" method="GET" path="/" action="Application\Action\Http\Index"/>
    <route name="product.show" method="GET" path="/products/{id:\d+}" action="Application\Action\Http\ShowProduct"/>
    <command name="hello" action="Application\Action\Cli\Hello"/>
</routes>
```

## 3. Try it

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

The last one is the `trailing-slash="redirect"` policy: every page has exactly one URL.

## 4. A test

`tests/Unit/ShowProductTest.php` — the action through the real router and middleware:

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

## Next steps

- [Routing](../http/routing.md): parameters, query parameters, URL generation
- [Actions](../http/actions.md) and [requests](../http/requests.md)
- [Views and content negotiation](../http/content-negotiation.md)

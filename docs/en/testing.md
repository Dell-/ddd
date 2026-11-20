# Testing

Tests use [Codeception](https://codeception.com/) 5 and run in the dev container:

```sh
docker compose run --rm app vendor/bin/codecept run              # all suites
docker compose run --rm app vendor/bin/codecept run Unit         # one suite
docker compose run --rm app vendor/bin/codecept run Unit Http/Middleware/CsrfMiddlewareTest
docker compose run --rm app vendor/bin/codecept run Api --debug  # with requests and responses
```

| Suite | What | How |
|---|---|---|
| `Unit` (`tests/Unit`) | classes and the application wired by the container | PHPUnit-style test cases (`Codeception\Test\Unit`) |
| `Api` (`tests/Api`) | the application over real HTTP | Codeception starts `php -S` with the test configuration; `REST` + `PhpBrowser` modules |

## The test application

`tests/Support/Data/etc` is a complete configuration (`di.xml`, `routes.xml`) with test routes and
fixtures (`tests/Support/Fixture`): a REST resource `/items`, uploads, downloads, content negotiation,
CORS origins, a fixed CSRF secret and a template engine.

```php
use Tests\Support\TestApplication;

$container = TestApplication::createContainer();          // Bootstrap::createContainer(tests/Support/Data/etc)
$kernel = $container->get(\Core\Http\Kernel::class);      // the whole middleware pipeline
```

## Helpers

| Helper | |
|---|---|
| `Tests\Support\Requests::make($method, $uri, $headers, $body, $cookies)` | a server request built like a real one (`ServerRequestFactory`) |
| `Tests\Support\CallableHandler` | PSR-15 handler from a closure, remembers the request — for middleware tests |
| `Tests\Support\StringReader` | a configuration reader from an XML string — routes and `di.xml` in a test |
| `Tests\Support\TempDirectoryTrait` | a temporary directory with files, removed after the test |

## Testing an action through the kernel

```php
final class ItemsTest extends Unit
{
    public function testCreate(): void
    {
        $kernel = TestApplication::createContainer()->get(Kernel::class);

        $response = $kernel->handle(Requests::make('POST', '/items', ['Content-Type' => 'application/json'], '{"name":"Desk"}'));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('/items/42', $response->getHeaderLine('Location'));
    }
}
```

## Testing middleware

```php
public function testRequestId(): void
{
    $handler = new CallableHandler();
    $response = new RequestIdMiddleware()->process(Requests::make('GET', '/', ['X-Request-Id' => 'abc']), $handler);

    $this->assertSame('abc', $handler->request?->getAttribute('request_id'));
    $this->assertSame('abc', $response->getHeaderLine('X-Request-Id'));
}
```

## Testing routes without files

```php
$router = new Router(new Config(new StringReader(
    '<routes><route name="a" method="GET" path="/a/{id:\d+}" action="A"/></routes>'
), new Converter()));

$this->assertSame(['id' => '5'], $router->match('GET', '/a/5')->parameters);
```

## HTTP tests

```php
final class ItemsCest
{
    public function create(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', ['name' => 'Desk']);

        $I->seeResponseCodeIs(201);
        $I->seeHttpHeader('Location', '/items/42');
        $I->seeResponseContainsJson(['name' => 'Desk']);
    }
}
```

Two things to know: PhpBrowser keeps cookies like a browser, so after a request that set `XSRF-TOKEN`
unsafe requests need the token (`$I->haveHttpHeader('X-XSRF-TOKEN', $I->grabCookie('XSRF-TOKEN'))`) or
`$I->resetCookie('XSRF-TOKEN')`. And the PhpBrowser connector reports `text/html` for responses without
`Content-Type`; check such responses with `$I->executeInGuzzle(...)`.

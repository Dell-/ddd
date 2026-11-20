# Тестування

Тести використовують [Codeception](https://codeception.com/) 5 і запускаються в dev-контейнері:

```sh
docker compose run --rm app vendor/bin/codecept run              # all suites
docker compose run --rm app vendor/bin/codecept run Unit         # one suite
docker compose run --rm app vendor/bin/codecept run Unit Http/Middleware/CsrfMiddlewareTest
docker compose run --rm app vendor/bin/codecept run Api --debug  # with requests and responses
```

| Набір | Що | Як |
|---|---|---|
| `Unit` (`tests/Unit`) | класи та застосунок, зібраний контейнером | тест-кейси у стилі PHPUnit (`Codeception\Test\Unit`) |
| `Api` (`tests/Api`) | застосунок через справжній HTTP | Codeception запускає `php -S` з тестовою конфігурацією; модулі `REST` + `PhpBrowser` |

## Тестовий застосунок

`tests/Support/Data/etc` — це повна конфігурація (`di.xml`, `routes.xml`) з тестовими маршрутами та
фікстурами (`tests/Support/Fixture`): REST-ресурс `/items`, завантаження на сервер, віддача файлів, узгодження вмісту,
джерела (origins) CORS, фіксований секрет CSRF і шаблонізатор.

```php
use Tests\Support\TestApplication;

$container = TestApplication::createContainer();          // Bootstrap::createContainer(tests/Support/Data/etc)
$kernel = $container->get(\Core\Http\Kernel::class);      // the whole middleware pipeline
```

## Допоміжні засоби

| Допоміжний засіб | |
|---|---|
| `Tests\Support\Requests::make($method, $uri, $headers, $body, $cookies)` | серверний запит, побудований як справжній (`ServerRequestFactory`) |
| `Tests\Support\CallableHandler` | обробник PSR-15 із замикання, запам’ятовує запит — для тестів middleware |
| `Tests\Support\StringReader` | зчитувач конфігурації з XML-рядка — маршрути та `di.xml` у тесті |
| `Tests\Support\TempDirectoryTrait` | тимчасовий каталог із файлами, що видаляється після тесту |

## Тестування action через kernel

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

## Тестування middleware

```php
public function testRequestId(): void
{
    $handler = new CallableHandler();
    $response = new RequestIdMiddleware()->process(Requests::make('GET', '/', ['X-Request-Id' => 'abc']), $handler);

    $this->assertSame('abc', $handler->request?->getAttribute('request_id'));
    $this->assertSame('abc', $response->getHeaderLine('X-Request-Id'));
}
```

## Тестування маршрутів без файлів

```php
$router = new Router(new Config(new StringReader(
    '<routes><route name="a" method="GET" path="/a/{id:\d+}" action="A"/></routes>'
), new Converter()));

$this->assertSame(['id' => '5'], $router->match('GET', '/a/5')->parameters);
```

## HTTP-тести

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

Два моменти, які варто знати: PhpBrowser зберігає cookie як браузер, тож після запиту, що встановив `XSRF-TOKEN`,
небезпечним запитам потрібен токен (`$I->haveHttpHeader('X-XSRF-TOKEN', $I->grabCookie('XSRF-TOKEN'))`) або
`$I->resetCookie('XSRF-TOKEN')`. І конектор PhpBrowser повідомляє `text/html` для відповідей без
`Content-Type`; перевіряйте такі відповіді через `$I->executeInGuzzle(...)`.

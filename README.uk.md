# ddd

> 🇬🇧 [English version](README.md)

Невеликий фреймворк для PHP 8.4+, який серйозно ставиться до специфікацій HTTP: **PSR-7 / PSR-15 / PSR-17**,
узгодження вмісту й умовні запити за **RFC 9110**, опис проблем (problem details, **RFC 9457**), JSON Lines і
JSON Text Sequences, потокове завантаження multipart для будь-якого методу, CORS за стандартом **Fetch**,
захист від CSRF без сесій і правила URL, явно задані в конфігурації.

**[Документація](docs/uk/README.md)** · [Documentation](docs/en/README.md)

```php
final class ShowItem extends AbstractAction
{
    public function __construct(private readonly ItemRepository $items) {}      // autowired

    public function __invoke(ServerRequestInterface $request): View
    {
        $item = $this->items->find((int) $request->getAttribute('id'))
            ?? throw new NotFoundException('Item not found.');                   // → 404 problem details

        return $this->view($item, 'item/show');                                  // → JSON, XML or HTML by Accept
    }
}
```

```xml
<route name="item.show" method="GET" path="/items/{id:\d+}" action="App\Item\ShowItem"/>
```

## Початок роботи

```sh
git clone git@github.com:Dell-/ddd.git && cd ddd
docker compose up -d --build                                    # http://localhost:8080
docker compose run --rm app php bin/console hello --name=World  # Hello, World!
docker compose run --rm app vendor/bin/codecept run             # the test suites
```

Потрібен PHP 8.4+ (Docker-образ використовує PHP 8.5). Див. [Встановлення](docs/uk/getting-started/installation.md)
і [Швидкий старт](docs/uk/getting-started/quickstart.md).

---

## Демо

Відповіді нижче справжні: їх повертає тестовий застосунок із `tests/Support/Data/etc`
(заголовки безпеки прибрано для стислості).

### Один action — формат, якого хоче клієнт

```
$ curl -i localhost:8080/items/7                         $ curl -H 'Accept: application/xml' localhost:8080/items/7
HTTP/1.1 200 OK                                          <?xml version="1.0" encoding="UTF-8"?>
Content-Type: application/json                           <response><id>7</id><name>Item 7</name></response>
Vary: Accept
                                                         $ curl -H 'Accept: text/html' localhost:8080/items/7
{"id":7,"name":"Item 7"}                                 <h1>Item 7</h1>
```

Action повертає `View`; рендерер для JSON, XML, HTML (через ваш шаблонізатор) чи тексту обирається за
`Accept` (RFC 9110 §12), з `Vary: Accept` і `406`, якщо жоден формат не підходить. Новий формат — це одна
реалізація `RendererInterface`. → [Представлення та узгодження вмісту](docs/uk/http/content-negotiation.md)

### URL відповідають конфігурації

```xml
<routes trailing-slash="redirect">
    <route name="item.list" method="GET" path="/items" action="App\Item\ListItems">
        <query name="page" requirement="\d+" default="1"/>
        <query name="limit" requirement="\d+" default="3"/>
    </route>
</routes>
```

```php
$urls->generate('item.list', ['page' => 3, 'limit' => 3]);   // "/items?page=3&limit=3" — query parameters go to the query
$urls->generate('item.list', ['color' => 'red']);            // InvalidArgumentException: no parameter "color"
```

```
$ curl -i 'localhost:8080/items/?page=2'                 $ curl -i 'localhost:8080/items?page=abc'
HTTP/1.1 301 Moved Permanently                           HTTP/1.1 400 Bad Request
Location: /items?page=2                                  Content-Type: application/problem+json

$ curl -i -X PUT localhost:8080/items/5/                 {"type":"about:blank","title":"Bad Request","status":400,
HTTP/1.1 308 Permanent Redirect                           "detail":"Invalid query parameters: \"page\" must match \"\\d+\".",
Location: /items/5                                        "errors":{"page":"must match \"\\d+\""}}
```

Слеш у кінці URL — явна політика: `redirect` (одна сторінка — один URL), `strict` (404) або `ignore`;
задається на `<routes>` і для окремих маршрутів. → [Маршрутизація](docs/uk/http/routing.md)

### Семантика REST одразу

```
$ curl -i -X POST -H 'Content-Type: application/json' -d '{"name":"Desk"}' localhost:8080/items
HTTP/1.1 201 Created
Location: /items/42

{"id":42,"name":"Desk"}

$ curl -i -X DELETE localhost:8080/items                 $ curl -i -X OPTIONS localhost:8080/items/1
HTTP/1.1 405 Method Not Allowed                          HTTP/1.1 204 No Content
Allow: GET, HEAD, POST, OPTIONS                          Allow: GET, HEAD, PUT, PATCH, DELETE, OPTIONS
```

HEAD обслуговують GET-маршрути без тіла, `POST` + `_method` працює для HTML-форм, тіла `PUT`/`PATCH`
розбираються так само, як `POST`. → [Actions](docs/uk/http/actions.md) · [Запити](docs/uk/http/requests.md)

### Помилки — це опис проблеми (RFC 9457)

```php
throw new UnprocessableContentException(['name' => 'The name is required.']);
```

```
HTTP/1.1 422 Unprocessable Content
Content-Type: application/problem+json

{"type":"about:blank","title":"Unprocessable Content","status":422,
 "detail":"The content contains invalid values.","errors":{"name":"The name is required."}}
```

```
$ curl -H 'Accept: application/xml' localhost:8080/nope
<problem xmlns="urn:ietf:rfc:7807"><type>about:blank</type><title>Not Found</title><status>404</status>
<detail>No route for "GET /nope".</detail></problem>
```

`Status` — enum усіх зареєстрованих кодів стану; для кожного поширеного 4xx/5xx є виняток, який додає
заголовки, обов'язкові для його коду (`Allow`, `WWW-Authenticate`, `Retry-After`, …). → [Помилки](docs/uk/http/errors.md)

### JSON, зокрема порядково

```
$ printf '{"id":1}\n{"id":2}\n' | curl -s -X POST -H 'Content-Type: application/x-ndjson' --data-binary @- localhost:8080/records
{"count":2,"records":[{"id":1},{"id":2}]}

$ curl -i localhost:8080/records
HTTP/1.1 200 OK
Content-Type: application/x-ndjson

{"id":1}
{"id":2}
{"id":3}
```

Тіла запитів: JSON і всі типи `+json`, JSON Lines / NDJSON, JSON Text Sequences (RFC 7464), форми (без
спотворення імен, як у PHP), multipart, XML (захищений від XXE). → [Запити](docs/uk/http/requests.md#тіла-запитів)

### Файли: будь-яким способом на сервер, ефективно з сервера

```
$ curl -X PUT -F title=Report -F 'document=@doc.txt;type=text/plain' localhost:8080/upload     # multipart with PUT
{"method":"PUT","title":"Report","files":{"document":[{"ok":true,"name":"doc.txt","type":"text/plain","size":12,...}]}}

$ curl -X PUT -H 'Content-Type: text/plain' \
       -H "Content-Disposition: attachment; filename*=UTF-8''%D0%B7%D0%B2%D1%96%D1%82.txt" \
       --data-binary @doc.txt localhost:8080/files/report                                    # the body is the file
{"name":"звіт.txt","clientType":"text/plain","type":"text/plain","size":12,...}
```

```php
$file = $request->getUploadedFile('document') ?? $request->getBodyAsFile();
$file->hasMediaType('image/*');            // detected from the content, not the client's claim
$path = $file->store('/var/uploads');      // random safe name, never overwrites
```

```
$ curl -i -H 'Range: bytes=0-4' localhost:8080/download/hello.txt
HTTP/1.1 206 Partial Content
Content-Range: bytes 0-4/24
Content-Disposition: attachment; filename="______ hello.txt"; filename*=UTF-8''%D0%9F%D1%80%D0%B8%D0%B2%D1%96%D1%82%20hello.txt
ETag: "69ea5ea99e1091849c1747c66618cf82"
Accept-Ranges: bytes

Hello
```

`FileResponse` обробляє `ETag`/`Last-Modified` (304, 412), діапазони байтів (206, 416) та імена файлів у
UTF-8 (RFC 6266/8187). → [Файли](docs/uk/http/files.md)

### CORS і CSRF

```
$ curl -i -X OPTIONS -H 'Origin: https://app.example.com' -H 'Access-Control-Request-Method: PUT' \
       -H 'Access-Control-Request-Headers: content-type' localhost:8080/items/1
HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://app.example.com
Access-Control-Allow-Methods: GET, HEAD, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 600
```

```
GET  /                                   → Set-Cookie: XSRF-TOKEN=MpON2V….4wsDY6…; Path=/; SameSite=Lax
POST /items  (cookie, no token)          → 403 {"title":"Forbidden","detail":"Invalid or missing CSRF token."}
POST /items  (cookie + X-XSRF-TOKEN)     → 201 Created
```

Захист від CSRF — підписаний double-submit cookie плюс перевірка `Origin` / `Sec-Fetch-Site` (OWASP); він
одразу працює з Angular і axios і не заважає API-клієнтам без cookie. Заголовки безпеки (nosniff,
frame-ancestors, Referrer-Policy, HSTS на https) є в кожній відповіді, зокрема в помилках.
→ [Безпека](docs/uk/http/security.md)

### Middleware і конфігурація

```xml
<instance class="Core\Http\Kernel" shared="true">
    <argument name="handler"><object class="Core\Http\RouteHandler"/></argument>
    <argument name="middleware">
        <array>
            <item><object class="Core\Http\Middleware\SecurityHeadersMiddleware"/></item>
            <item>
                <object class="Core\Http\Middleware\CorsMiddleware">
                    <argument name="allowedOrigins"><env name="APP_CORS_ORIGINS" default=""/></argument>
                </object>
            </item>
            <item><object class="App\Http\RequestIdMiddleware"/></item>   <!-- any PSR-15 middleware -->
            ...
        </array>
    </argument>
</instance>
```

Увесь застосунок зібрано в `di.xml` (перевіряється XML Schema, зливається з `local.di.xml`); налаштування
беруться зі змінних оточення (`APP_SECRET`, `APP_DEBUG`, `APP_CORS_ORIGINS`, …).
→ [Впровадження залежностей](docs/uk/architecture/dependency-injection.md) · [Middleware](docs/uk/http/middleware.md)

### Консоль

```php
final class Hello implements CommandInterface
{
    public function run(Input $input, Output $output): int
    {
        $output->writeln(sprintf('Hello, %s!', $input->getOption('name', 'World')));

        return self::SUCCESS;
    }
}
```

```
$ bin/console hello --name=World          $ bin/console nope; echo $?
Hello, World!                             Command "nope" not found.
                                          1
```

→ [Консоль](docs/uk/console.md)

---

## Тестування

```sh
docker compose run --rm app vendor/bin/codecept run           # Unit + Api (real HTTP against php -S)
```

```php
$kernel = TestApplication::createContainer()->get(Kernel::class);
$response = $kernel->handle(Requests::make('POST', '/items', ['Content-Type' => 'application/json'], '{"name":"Desk"}'));
```

→ [Тестування](docs/uk/testing.md)

## Документація

| | |
|---|---|
| Початок роботи | [Встановлення](docs/uk/getting-started/installation.md) · [Швидкий старт](docs/uk/getting-started/quickstart.md) · [Структура каталогів](docs/uk/getting-started/directory-structure.md) |
| Архітектура | [Життєвий цикл запиту](docs/uk/architecture/lifecycle.md) · [Впровадження залежностей](docs/uk/architecture/dependency-injection.md) · [Конфігурація](docs/uk/architecture/configuration.md) |
| HTTP | [Маршрутизація](docs/uk/http/routing.md) · [Actions](docs/uk/http/actions.md) · [Запити](docs/uk/http/requests.md) · [Відповіді](docs/uk/http/responses.md) · [Узгодження вмісту](docs/uk/http/content-negotiation.md) · [Помилки](docs/uk/http/errors.md) · [Файли](docs/uk/http/files.md) · [Middleware](docs/uk/http/middleware.md) · [Безпека](docs/uk/http/security.md) |
| Ще | [Консоль](docs/uk/console.md) · [Тестування](docs/uk/testing.md) · [Стандарти](docs/uk/reference/standards.md) · [Коди стану](docs/uk/reference/status-codes.md) · [Схеми конфігурації](docs/uk/reference/configuration-schemas.md) |

## Ліцензія

MIT — див. [LICENSE](LICENSE).

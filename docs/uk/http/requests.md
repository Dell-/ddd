# Запити

Actions отримують `Core\Http\Message\ServerRequestInterface`: PSR-7 `ServerRequestInterface` із
допоміжними методами. Запити незмінні (immutable): методи `with*()` повертають новий запит.

## Де лежать дані

| | Метод | Приклад |
|---|---|---|
| Параметри маршруту | `getAttribute('id')`, `getRouteParams()`, `getRouteName()` | `/items/{id}` |
| Рядок запиту | `getQueryParams()`, `getQueryParam('filter.status')` | `?filter[status]=new` |
| Тіло | `getParsedBody()`, `getBodyParam('address.city')` | JSON, форма, multipart, XML, … |
| Необроблене тіло | `getBody()` (потік), `(string) $request->getBody()` | |
| Будь-які вхідні дані | `input('id')` — параметр маршруту, потім тіло, потім рядок запиту | |
| Заголовки | `getHeaderLine('Accept-Language')`, `hasHeader()`, `getHeader()` | без урахування регістру |
| Cookies | `getCookieParams()`, `getCookieParam('theme')` | |
| Файли | `getUploadedFile('avatar')`, `getUploadedFileList('photos')`, `getBodyAsFile()` | див. [Файли](files.md) |
| Сервер | `getServerParams()`, `getUri()`, `getMethod()`, `getProtocolVersion()` | |

Вкладені значення адресуються через крапки або дужки: `getQueryParam('filter.status')` і
`getQueryParam('filter[status]')` — те саме. Відсутні значення повертають значення за замовчуванням (`null`).

```php
$page = (int) $request->getQueryParam('page', 1);
$email = $request->getBodyParam('user.email');
$id = $request->input('id');
```

## Тіла запитів

`BodyParsingMiddleware` розбирає тіло за його `Content-Type` **для кожного методу** — сам PHP розбирає лише
форми та multipart у `POST`:

| Content-Type | Розібране тіло |
|---|---|
| `application/json`, будь-який `…+json` (`application/merge-patch+json`, `application/vnd.api+json`, …) | масив (об'єкти як асоціативні масиви); цілі числа більші за `PHP_INT_MAX` — як рядки |
| `application/x-ndjson`, `application/jsonl`, `application/x-jsonlines`, `application/jsonlines`, `application/x-jsonl` | список значень, один JSON-текст на рядок, порожні рядки ігноруються |
| `application/json-seq` (RFC 7464) | список значень (записи починаються з `RS`, 0x1E) |
| `application/x-www-form-urlencoded` | масив, вкладеність `a[b][]=1`; імена зберігаються (`a.b` залишається `a.b`, PHP зробив би `a_b`) |
| `multipart/form-data` | поля як масив, файли як завантажені файли (також для `PUT`/`PATCH`) |
| `application/xml`, `text/xml`, будь-який `…+xml` | `SimpleXMLElement`; DOCTYPE відхиляється (XXE) |
| усе інше | не розбирається: `getParsedBody()` дорівнює `null`, читайте `getBody()` або `getBodyAsFile()` |

Некоректні тіла дають **400** з точним описом, наприклад `Invalid JSON on line 3: Syntax error.`; надто багато
записів або частин — **413**. JSON-скаляр верхнього рівня (`"text"`, `42`) відхиляється: PSR-7 дозволяє як
розібране тіло лише масиви й об'єкти.

```
POST /records
Content-Type: application/x-ndjson

{"id":1}
{"id":2}
```

```php
$records = $request->getParsedBody();   // [['id' => 1], ['id' => 2]]
```

Парсери налаштовуються в `di.xml` (`parsers` у `BodyParsingMiddleware`); для іншого формату реалізуйте
`Core\Http\Body\BodyParserInterface`.

## Допоміжні методи узгодження вмісту

```php
$request->getMediaType();              // MediaType of Content-Type: ->essence, ->suffix, ->charset, ->isJson()
$request->getAccept()->negotiate(['application/json', 'text/csv']);   // the client's choice or null
$request->accepts('application/json'); // RFC 9110 §12.5.1 rules, q-values, wildcards
$request->getContentLength();          // ?int
```

## Інші допоміжні методи

```php
$request->getMethodEnum();     // Core\Http\Method::Patch or null for non-standard methods
$request->isMethod(Method::Post);
$request->isSecure();          // https
$request->getOrigin();         // "https://example.com:8443"
```

## Створення запитів

```php
$request = ServerRequestFactory::fromGlobals();      // what web/index.php does
$request = ServerRequestFactory::fromArrays($server, $query, $post, $cookies, $files, $body);
$request = new HttpFactory()->createServerRequest('GET', 'https://example.com/items');   // PSR-17
```

Заголовкам проксі (`X-Forwarded-For`, `X-Forwarded-Proto`, `Forwarded`) не довіряють: URI — це те, що
повідомляє вебсервер.

# Маршрутизація

Маршрути розташовані в `routes.xml` каталогу конфігурації, поруч із консольними командами.

```xml
<routes trailing-slash="redirect"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="../../Core/Route/etc/routes.xsd">
    <route name="item.list" method="GET" path="/items" action="App\Item\ListItems">
        <query name="page" requirement="\d+" default="1"/>
        <query name="q"/>
    </route>
    <route name="item.create" method="POST"      path="/items"           action="App\Item\CreateItem"/>
    <route name="item.show"   method="GET"       path="/items/{id:\d+}"  action="App\Item\ShowItem"/>
    <route name="item.update" method="PUT PATCH" path="/items/{id:\d+}"  action="App\Item\UpdateItem"/>
    <route name="item.delete" method="DELETE"    path="/items/{id:\d+}"  action="App\Item\DeleteItem"/>
    <route name="docs"        method="GET"       path="/docs/"           action="App\Docs"/>

    <command name="hello" action="App\Cli\Hello"/>
</routes>
```

| Атрибут | |
|---|---|
| `name` | унікальна назва, використовується для генерації URL і в перевизначальних файлах |
| `method` | через пробіл: `GET`, `HEAD`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS` |
| `path` | шаблон шляху, починається з `/` |
| `action` | клас [action](actions.md) |
| `trailing-slash` | `redirect`, `strict` або `ignore`; перевизначає політику `<routes>` |

Маршрути перевіряються **у порядку оголошення, перший збіг перемагає**: розміщуйте `/items/new` перед `/items/{id}`.

## Параметри шляху

| Шаблон | Відповідає | Параметри |
|---|---|---|
| `/items/{id}` | `/items/abc` — один непорожній сегмент шляху | `id=abc` |
| `/items/{id:\d+}` | `/items/42`, але не `/items/4x` | `id=42` |
| `/archive/{year:\d{4}}/{slug}` | `/archive/2026/hello` | `year=2026`, `slug=hello` |
| `/files/{path:.+}` | `/files/a/b/c.txt` — кілька сегментів | `path=a/b/c.txt` |
| `/file/{name}.{ext:[a-z]+}` | `/file/report.pdf` | `name=report`, `ext=pdf` |
| `/{lang:en\|uk}/about` | `/en/about`, `/uk/about` (з прив'язкою) | `lang=en` |

Регулярний вираз прив'язаний до початку й кінця, а шлях перед зіставленням URL-декодується
(`/tags/caf%C3%A9` → `café`), крім `%2F` і `%25`: закодований слеш залишається всередині одного
параметра (`/tags/a%2Fb` → `tag=a/b`), і ніщо не декодується двічі. Некоректні шаблони (`{id`, дублікати
назв, зламаний регулярний вираз) спричиняють помилку під час завантаження маршрутів.

В action:

```php
$id = $request->getAttribute('id');        // "42" — parameters are strings
$params = $request->getRouteParams();      // ['id' => '42']
$name = $request->getRouteName();          // "item.show"
```

## Параметри запиту (query)

Параметри запиту, на які спирається маршрут, оголошуються, тож вони валідуються, мають значення за
замовчуванням і беруть участь у генерації URL:

```xml
<route name="search" method="GET" path="/search" action="App\Search">
    <query name="q" required="true"/>
    <query name="page" requirement="\d+" default="1"/>
    <query name="tags" requirement="[a-z]+"/>          <!-- tags[]=a&tags[]=b: every item is checked -->
</route>
```

- Некоректне або відсутнє обов'язкове значення дає **400** з розширенням problem `errors`:

  ```
  GET /search?page=abc
  HTTP/1.1 400 Bad Request
  Content-Type: application/problem+json

  {"type":"about:blank","title":"Bad Request","status":400,
   "detail":"Invalid query parameters: \"q\" is required; \"page\" must match \"\\d+\".",
   "errors":{"q":"is required","page":"must match \"\\d+\""}}
  ```

- Відсутнє необов'язкове значення отримує значення за замовчуванням: `$request->getQueryParam('page')` дорівнює `"1"` для `/search?q=x`.
- Необ'явлені параметри запиту передаються далі без перевірки.

## Слеш у кінці (trailing slash)

`/items` і `/items/` — різні URL. Що відбувається з іншою формою налаштованого шляху, визначає
явна політика, яку задають для всіх маршрутів на `<routes>` і можна перевизначити для окремого маршруту:

| `trailing-slash` | Запит іншої форми |
|---|---|
| `redirect` (за замовчуванням) | `301 Moved Permanently` (GET, HEAD) або `308 Permanent Redirect` (інші методи: зберігає метод і тіло) на налаштовану форму, із рядком запиту |
| `strict` | `404 Not Found` |
| `ignore` | обслуговується тим самим маршрутом, без перенаправлення. URL генеруються в налаштованій формі; сторінка має два URL, тому додайте `<link rel="canonical">` для пошукових систем |

```
GET /items/?page=2                     GET /docs                      PUT /items/5/
HTTP/1.1 301 Moved Permanently         HTTP/1.1 301 Moved Permanently HTTP/1.1 308 Permanent Redirect
Location: /items?page=2                Location: /docs/               Location: /items/5
```

`redirect` дає кожному ресурсу рівно один URL, чого й очікують пошукові системи та кеші.
`strict` підходить для API, клієнти яких мусять використовувати точні URL (вебхуки, підписані URL).

## Методи

- **405 Method Not Allowed** із заголовком `Allow`, коли шлях існує, але не для цього методу. `Allow` містить
  методи всіх маршрутів, що відповідають шляху, а також `HEAD` (якщо є `GET`) і `OPTIONS`.
- **HEAD** обслуговується маршрутом `GET`, без тіла (RFC 9110 §9.3.2); явний маршрут `HEAD` має пріоритет.
- На **OPTIONS** відповідає `204 No Content` із `Allow` (RFC 9110 §9.3.7), якщо немає маршруту `OPTIONS`.
  Попередні CORS-запити (preflight) обробляються раніше, у [`CorsMiddleware`](security.md#cors).
- **Перевизначення методу**: HTML-форми надсилають лише GET і POST; `POST` із `_method=DELETE` (поле форми) або
  `X-HTTP-Method-Override: DELETE` розглядається як `DELETE`. Лише `PUT`, `PATCH`, `DELETE`.

```
DELETE /items                                   OPTIONS /items/1
HTTP/1.1 405 Method Not Allowed                 HTTP/1.1 204 No Content
Allow: GET, HEAD, POST, OPTIONS                 Allow: GET, HEAD, PUT, PATCH, DELETE, OPTIONS
```

## Генерація URL

Впровадьте `Core\Route\UrlGeneratorInterface` (його реалізує `Router`):

```php
final class CreateItem extends AbstractAction
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // ...
        return $this->created($this->urls->generate('item.show', ['id' => $item->id]), $item);
    }
}
```

Параметри потрапляють **туди, де їх оголошує маршрут**: параметри шляху — у шлях, оголошені параметри
запиту — у рядок запиту.

```php
$urls->generate('item.show', ['id' => 42]);                      // /items/42
$urls->generate('item.list', ['page' => 2, 'q' => 'red desk']);  // /items?page=2&q=red%20desk
$urls->generate('docs');                                         // /docs/  (the configured form)
$urls->generate('tag', ['tag' => 'a/b']);                        // /tags/a%2Fb
```

Помилки — це винятки, тож зламане посилання не можна згенерувати непомітно:

| Виклик | Помилка |
|---|---|
| `generate('item.list', ['color' => 'red'])` | `Route "item.list" has no parameter "color" (path: -; query: page, q)` |
| `generate('item.show', [])` | `Route "item.show" requires path parameter "id"` |
| `generate('item.show', ['id' => 'abc'])` | `Parameter "id" of route "item.show" must match "\d+"` |
| `generate('search', [])` | `Query parameter "q" of route "search" is required` |
| `generate('nope')` | `Route "nope" does not exist` |

Значення `null` у параметрах запиту пропускаються, `true`/`false` стають `1`/`0`.

## Пряме використання маршрутизатора

```php
$match = $router->match('GET', '/items/42', $query);   // RouteMatch: ->route, ->parameters, ->query
$router->getAllowedMethods('/items');                    // ['GET', 'HEAD', 'POST', 'OPTIONS']
$router->findCanonicalPath('/items/');                   // '/items' (redirect policy) or null
$router->matchCommand('hello');                          // command class
```

`match()` кидає `NotFoundException`, `MethodNotAllowedException` або `HttpException` з кодом 400.

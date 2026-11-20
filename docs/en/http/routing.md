# Routing

Routes live in `routes.xml` of the configuration directory, next to the console commands.

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

| Attribute | |
|---|---|
| `name` | unique name, used for URL generation and in overriding files |
| `method` | space separated: `GET`, `HEAD`, `POST`, `PUT`, `PATCH`, `DELETE`, `OPTIONS` |
| `path` | path pattern, starts with `/` |
| `action` | class of the [action](actions.md) |
| `trailing-slash` | `redirect`, `strict` or `ignore`; overrides the `<routes>` policy |

Routes are checked **in declaration order, the first match wins**: put `/items/new` before `/items/{id}`.

## Path parameters

| Pattern | Matches | Parameters |
|---|---|---|
| `/items/{id}` | `/items/abc` — one path segment, not empty | `id=abc` |
| `/items/{id:\d+}` | `/items/42`, not `/items/4x` | `id=42` |
| `/archive/{year:\d{4}}/{slug}` | `/archive/2026/hello` | `year=2026`, `slug=hello` |
| `/files/{path:.+}` | `/files/a/b/c.txt` — several segments | `path=a/b/c.txt` |
| `/file/{name}.{ext:[a-z]+}` | `/file/report.pdf` | `name=report`, `ext=pdf` |
| `/{lang:en\|uk}/about` | `/en/about`, `/uk/about` (anchored) | `lang=en` |

The regex is anchored and the path is URL-decoded before matching (`/tags/caf%C3%A9` → `café`), except
`%2F` and `%25`: an encoded slash stays inside one parameter (`/tags/a%2Fb` → `tag=a/b`) and nothing is
decoded twice. Invalid patterns (`{id`, duplicate names, a broken regex) fail when the routes are loaded.

In the action:

```php
$id = $request->getAttribute('id');        // "42" — parameters are strings
$params = $request->getRouteParams();      // ['id' => '42']
$name = $request->getRouteName();          // "item.show"
```

## Query parameters

Query parameters a route relies on are declared, so they are validated, have defaults and take part in URL
generation:

```xml
<route name="search" method="GET" path="/search" action="App\Search">
    <query name="q" required="true"/>
    <query name="page" requirement="\d+" default="1"/>
    <query name="tags" requirement="[a-z]+"/>          <!-- tags[]=a&tags[]=b: every item is checked -->
</route>
```

- An invalid or missing required value is **400** with the problem extension `errors`:

  ```
  GET /search?page=abc
  HTTP/1.1 400 Bad Request
  Content-Type: application/problem+json

  {"type":"about:blank","title":"Bad Request","status":400,
   "detail":"Invalid query parameters: \"q\" is required; \"page\" must match \"\\d+\".",
   "errors":{"q":"is required","page":"must match \"\\d+\""}}
  ```

- A missing optional value gets its default: `$request->getQueryParam('page')` is `"1"` for `/search?q=x`.
- Undeclared query parameters are passed through unchecked.

## Trailing slash

`/items` and `/items/` are different URLs. What happens to the other form of a configured path is an
explicit policy, set for all routes on `<routes>` and overridable per route:

| `trailing-slash` | Request for the other form |
|---|---|
| `redirect` (default) | `301 Moved Permanently` (GET, HEAD) or `308 Permanent Redirect` (other methods: keeps the method and the body) to the configured form, with the query string |
| `strict` | `404 Not Found` |
| `ignore` | served by the route as well, no redirect. URLs are generated in the configured form; the page has two URLs, so add `<link rel="canonical">` for search engines |

```
GET /items/?page=2                     GET /docs                      PUT /items/5/
HTTP/1.1 301 Moved Permanently         HTTP/1.1 301 Moved Permanently HTTP/1.1 308 Permanent Redirect
Location: /items?page=2                Location: /docs/               Location: /items/5
```

`redirect` gives every resource exactly one URL, which is what search engines and caches expect.
`strict` suits APIs whose clients must use exact URLs (webhooks, signed URLs).

## Methods

- **405 Method Not Allowed** with `Allow` when the path exists but not for the method. `Allow` lists the
  methods of all routes that match the path, plus `HEAD` (if there is `GET`) and `OPTIONS`.
- **HEAD** is served by the `GET` route, without the body (RFC 9110 §9.3.2); an explicit `HEAD` route wins.
- **OPTIONS** is answered with `204 No Content` and `Allow` (RFC 9110 §9.3.7) unless there is an `OPTIONS`
  route. CORS preflight requests are answered earlier by [`CorsMiddleware`](security.md#cors).
- **Method override**: HTML forms only send GET and POST; `POST` with `_method=DELETE` (form field) or
  `X-HTTP-Method-Override: DELETE` is treated as `DELETE`. Only `PUT`, `PATCH`, `DELETE`.

```
DELETE /items                                   OPTIONS /items/1
HTTP/1.1 405 Method Not Allowed                 HTTP/1.1 204 No Content
Allow: GET, HEAD, POST, OPTIONS                 Allow: GET, HEAD, PUT, PATCH, DELETE, OPTIONS
```

## Generating URLs

Inject `Core\Route\UrlGeneratorInterface` (implemented by the `Router`):

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

Parameters go **where the route declares them**: path parameters into the path, declared query
parameters into the query string.

```php
$urls->generate('item.show', ['id' => 42]);                      // /items/42
$urls->generate('item.list', ['page' => 2, 'q' => 'red desk']);  // /items?page=2&q=red%20desk
$urls->generate('docs');                                         // /docs/  (the configured form)
$urls->generate('tag', ['tag' => 'a/b']);                        // /tags/a%2Fb
```

Errors are exceptions, so a broken link cannot be generated silently:

| Call | Error |
|---|---|
| `generate('item.list', ['color' => 'red'])` | `Route "item.list" has no parameter "color" (path: -; query: page, q)` |
| `generate('item.show', [])` | `Route "item.show" requires path parameter "id"` |
| `generate('item.show', ['id' => 'abc'])` | `Parameter "id" of route "item.show" must match "\d+"` |
| `generate('search', [])` | `Query parameter "q" of route "search" is required` |
| `generate('nope')` | `Route "nope" does not exist` |

`null` query values are omitted, `true`/`false` become `1`/`0`.

## Using the router directly

```php
$match = $router->match('GET', '/items/42', $query);   // RouteMatch: ->route, ->parameters, ->query
$router->getAllowedMethods('/items');                    // ['GET', 'HEAD', 'POST', 'OPTIONS']
$router->findCanonicalPath('/items/');                   // '/items' (redirect policy) or null
$router->matchCommand('hello');                          // command class
```

`match()` throws `NotFoundException`, `MethodNotAllowedException` or a 400 `HttpException`.

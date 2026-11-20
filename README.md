# ddd

> 🇺🇦 [Українська версія](README.uk.md)

A small PHP 8.4+ framework that takes the HTTP specifications seriously: **PSR-7 / PSR-15 / PSR-17**, content
negotiation and conditional requests from **RFC 9110**, problem details (**RFC 9457**), JSON Lines and JSON
Text Sequences, streamed multipart uploads for every method, CORS by the **Fetch** standard, CSRF protection
without sessions, and URL rules that are explicit in the configuration.

**[Documentation](docs/en/README.md)** · [Документація](docs/uk/README.md)

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

## Getting started

```sh
git clone git@github.com:Dell-/ddd.git && cd ddd
docker compose up -d --build                                    # http://localhost:8080
docker compose run --rm app php bin/console hello --name=World  # Hello, World!
docker compose run --rm app vendor/bin/codecept run             # the test suites
```

Requires PHP 8.4+ (the Docker image uses PHP 8.5). See [Installation](docs/en/getting-started/installation.md)
and the [Quick start](docs/en/getting-started/quickstart.md).

---

## Demos

The responses below are real: they come from the test application in `tests/Support/Data/etc`
(security headers are left out for brevity).

### One action, the format the client wants

```
$ curl -i localhost:8080/items/7                         $ curl -H 'Accept: application/xml' localhost:8080/items/7
HTTP/1.1 200 OK                                          <?xml version="1.0" encoding="UTF-8"?>
Content-Type: application/json                           <response><id>7</id><name>Item 7</name></response>
Vary: Accept
                                                         $ curl -H 'Accept: text/html' localhost:8080/items/7
{"id":7,"name":"Item 7"}                                 <h1>Item 7</h1>
```

The action returns a `View`; renderers for JSON, XML, HTML (through your template engine) and text are
chosen by `Accept` (RFC 9110 §12), with `Vary: Accept` and `406` when nothing fits. New formats are a
`RendererInterface` away. → [Views and content negotiation](docs/en/http/content-negotiation.md)

### URLs follow the configuration

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

The trailing slash is an explicit policy — `redirect` (one URL per page), `strict` (404) or `ignore` — on
`<routes>` and per route. → [Routing](docs/en/http/routing.md)

### REST semantics out of the box

```
$ curl -i -X POST -H 'Content-Type: application/json' -d '{"name":"Desk"}' localhost:8080/items
HTTP/1.1 201 Created
Location: /items/42

{"id":42,"name":"Desk"}

$ curl -i -X DELETE localhost:8080/items                 $ curl -i -X OPTIONS localhost:8080/items/1
HTTP/1.1 405 Method Not Allowed                          HTTP/1.1 204 No Content
Allow: GET, HEAD, POST, OPTIONS                          Allow: GET, HEAD, PUT, PATCH, DELETE, OPTIONS
```

HEAD is served by GET routes without a body, `POST` + `_method` works for HTML forms, `PUT`/`PATCH` bodies
are parsed like `POST`. → [Actions](docs/en/http/actions.md) · [Requests](docs/en/http/requests.md)

### Errors are problem details (RFC 9457)

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

`Status` is an enum of every registered status code, and every common 4xx/5xx has an exception that carries
the headers its status requires (`Allow`, `WWW-Authenticate`, `Retry-After`, …). → [Errors](docs/en/http/errors.md)

### JSON, also line by line

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

Request bodies: JSON and every `+json` type, JSON Lines / NDJSON, JSON Text Sequences (RFC 7464), forms
(without PHP's name mangling), multipart, XML (XXE-safe). → [Requests](docs/en/http/requests.md#request-bodies)

### Files: every way in, efficiently out

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

`FileResponse` handles `ETag`/`Last-Modified` (304, 412), byte ranges (206, 416) and UTF-8 file names
(RFC 6266/8187). → [Files](docs/en/http/files.md)

### CORS and CSRF

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

CSRF uses a signed double-submit cookie plus `Origin` / `Sec-Fetch-Site` checks (OWASP), works with
Angular and axios out of the box and leaves cookie-less API clients alone. Security headers (nosniff,
frame-ancestors, Referrer-Policy, HSTS on https) are on every response, errors included.
→ [Security](docs/en/http/security.md)

### Middleware and configuration

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

The whole application is wired in `di.xml` (validated by an XML Schema, merged with `local.di.xml`);
settings come from the environment (`APP_SECRET`, `APP_DEBUG`, `APP_CORS_ORIGINS`, …).
→ [Dependency injection](docs/en/architecture/dependency-injection.md) · [Middleware](docs/en/http/middleware.md)

### Console

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

→ [Console](docs/en/console.md)

---

## Testing

```sh
docker compose run --rm app vendor/bin/codecept run           # Unit + Api (real HTTP against php -S)
```

```php
$kernel = TestApplication::createContainer()->get(Kernel::class);
$response = $kernel->handle(Requests::make('POST', '/items', ['Content-Type' => 'application/json'], '{"name":"Desk"}'));
```

→ [Testing](docs/en/testing.md)

## Documentation

| | |
|---|---|
| Getting started | [Installation](docs/en/getting-started/installation.md) · [Quick start](docs/en/getting-started/quickstart.md) · [Directory structure](docs/en/getting-started/directory-structure.md) |
| Architecture | [Request lifecycle](docs/en/architecture/lifecycle.md) · [Dependency injection](docs/en/architecture/dependency-injection.md) · [Configuration](docs/en/architecture/configuration.md) |
| HTTP | [Routing](docs/en/http/routing.md) · [Actions](docs/en/http/actions.md) · [Requests](docs/en/http/requests.md) · [Responses](docs/en/http/responses.md) · [Content negotiation](docs/en/http/content-negotiation.md) · [Errors](docs/en/http/errors.md) · [Files](docs/en/http/files.md) · [Middleware](docs/en/http/middleware.md) · [Security](docs/en/http/security.md) |
| More | [Console](docs/en/console.md) · [Testing](docs/en/testing.md) · [Standards](docs/en/reference/standards.md) · [Status codes](docs/en/reference/status-codes.md) · [Configuration schemas](docs/en/reference/configuration-schemas.md) |

## License

MIT — see [LICENSE](LICENSE).

# ddd

Lightweight PHP framework core: XML-configured DI container, XML routes, HTTP and console request handling.

Requires PHP 8.4+ (the Docker image uses PHP 8.5). Built on PSR-7, PSR-15 and PSR-17 and the HTTP RFCs.

## Run with Docker

```sh
docker compose up -d --build          # HTTP server on http://localhost:8080
curl localhost:8080/                  # -> Application\Action\Http\Index

docker compose run --rm app php bin/console hello --name=World   # -> Hello, World!

docker compose down
```

`app/`, `web/` and `bin/` are mounted into the container, so code changes apply without a rebuild.
After changing `composer.json`, update the lock file and rebuild:

```sh
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app \
    composer:2 composer update --no-install
docker compose build
```

Logs are written to `app/Application/runtime/app-YYYY-MM-DD.log`.

`compose.yaml` builds the `dev` target (with dev dependencies and tests).
The production image is the default target: `docker build -t ddd-app .`

## Tests

[Codeception](https://codeception.com/) suites:

- `tests/Unit` — unit tests
- `tests/Api` — HTTP tests: Codeception starts `php -S` with the test configuration
  (`tests/Support/Data/etc`: a sample REST resource, uploads, downloads, CORS/CSRF) and sends real requests

```sh
docker compose run --rm app vendor/bin/codecept run          # all suites
docker compose run --rm app vendor/bin/codecept run Api      # one suite
```

## Structure

- `app/Bootstrap.php` — builds the DI container from `di.xml` of the configuration directory
- `app/Application/etc/di.xml` — services, the HTTP middleware pipeline, response renderers
  (argument types: `string`, `int`, `float`, `bool`, `array`, `object`, `path`, `env`; `callback` calls a method after creation)
- `app/Application/etc/routes.xml` — HTTP routes and console commands
- `app/Core/Http` — HTTP layer on PSR-7 (messages), PSR-15 (middleware, handlers) and PSR-17 (factories)
- `web/index.php` — HTTP entry point, `bin/console` — console entry point

## Configuration (environment)

| Variable | Default | |
|---|---|---|
| `APP_SECRET` | — (required) | CSRF token signing key, at least 32 characters |
| `APP_DEBUG` | `false` | exception details in 500 responses and console errors |
| `APP_CORS_ORIGINS` | none | allowed CORS origins, comma/space separated (exact, `*` or `#regex#`) |
| `APP_MAX_BODY_SIZE` | `10485760` | request body limit in bytes (413 above it) |
| `APP_ETC_DIR` | `app/Application/etc` | configuration directory |

## Routing

```xml
<routes trailing-slash="redirect">
    <route name="item.list" method="GET" path="/items" action="App\Item\ListItems">
        <query name="page" requirement="\d+" default="1"/>
        <query name="q" required="true"/>
    </route>
    <route name="item.show"   method="GET"       path="/items/{id:\d+}"  action="App\Item\ShowItem"/>
    <route name="item.update" method="PUT PATCH" path="/items/{id:\d+}"  action="App\Item\UpdateItem"/>
    <route name="docs"        method="GET"       path="/docs/"           action="App\Docs"/>
    <route name="webhook"     method="POST"      path="/hooks/pay"       action="App\Hook" trailing-slash="strict"/>
    <route name="file"        method="GET"       path="/files/{path:.+}" action="App\File"/>
    <command name="hello" action="App\Cli\Hello"/>
</routes>
```

- Paths are matched exactly. The other form of a path (`/items/` for `/items`, `/docs` for `/docs/`) follows
  the `trailing-slash` policy, set on `<routes>` (for all routes) and overridable per `<route>`:

  | Value | The other form of the path |
  |---|---|
  | `redirect` (default) | 301 (GET/HEAD) / 308 (other methods) to the configured form: one URL per page |
  | `strict` | 404 |
  | `ignore` | served as well, no redirect (duplicate URLs: add `<link rel="canonical">`); URLs are generated in the configured form |
- `{name}` is one path segment, `{name:regex}` a regex (`{year:\d{4}}`, `{path:.+}`); values are URL-decoded,
  an encoded slash (`%2F`) stays inside the parameter.
- `<query>` declares query parameters: validated (400 with `errors`), defaults added.
- `UrlGeneratorInterface::generate('item.list', ['page' => 2, 'q' => 'php'])` → `/items?page=2&q=php`:
  path parameters go into the path, declared query parameters into the query string, anything else is an error.
- 404 for unknown paths, 405 with `Allow` for other methods, HEAD is served by GET routes,
  OPTIONS is answered with 204 and `Allow`.

## Actions

An action is a PSR-15 `RequestHandlerInterface` or, more conveniently, a `Core\Http\Action\ActionInterface`
(`AbstractAction` has helpers). Dependencies are constructor arguments (autowired).

```php
final class ShowItem extends AbstractAction
{
    public function __construct(private readonly ItemRepository $items) {}

    public function __invoke(ServerRequestInterface $request): View
    {
        $item = $this->items->find((int) $request->getAttribute('id'))
            ?? throw new NotFoundException('Item not found.');

        return $this->view($item, 'item/show');   // JSON, XML or HTML (template) by Accept
    }
}
```

Results: `ResponseInterface` as it is; `View` and data (arrays, `JsonSerializable`, scalars) are negotiated
by `Accept` (RFC 9110 §12, `Vary: Accept`, 406 if nothing fits); a string is HTML; `null` is 204.
Ready-made responses: `JsonResponse`, `JsonLinesResponse` (NDJSON / `application/json-seq`), `HtmlResponse`,
`TextResponse`, `XmlResponse`, `EmptyResponse`, `RedirectResponse`, `FileResponse`.

New formats: implement `Core\Http\View\RendererInterface` and add it to `ViewRenderer` in `di.xml`.
Templates: implement `TemplateEngineInterface` (Twig, Latte, … adapter) and set it as `HtmlRenderer`'s `engine`.

## Requests

`Core\Http\Message\ServerRequestInterface` (PSR-7 plus helpers): `getQueryParam('filter.status')`,
`getBodyParam('a.b')`, `input('id')` (route → body → query), `getCookieParam()`, `getRouteName()`,
`getMediaType()`, `accepts('application/json')`, `getOrigin()`, …

Bodies are parsed by `BodyParsingMiddleware` for every method: JSON and `+json` types (RFC 8259/6839),
JSON Lines / NDJSON, JSON Text Sequences (RFC 7464), `application/x-www-form-urlencoded` (names are not mangled),
`multipart/form-data` (RFC 7578, streamed), XML (DOCTYPE rejected). Malformed bodies are 400.

## Files

```php
$file = $request->getUploadedFile('avatar');          // multipart (POST, PUT, PATCH); "photos.0", "docs[passport]"
$files = $request->getUploadedFileList('photos');     // "photos[]"
$file = $request->getBodyAsFile();                    // REST: PUT /files/x with the file as the body
$file = UploadedFile::fromDataUri($json['avatar']);   // data URI / fromBase64() in JSON

$file->isOk(); $file->getErrorMessage();
$file->getMediaType();          // detected from the content (the client's claim: getClientMediaType())
$file->hasMediaType('image/*');
$path = $file->store('/var/uploads');                 // random safe name with the extension
```

`FileResponse` sends files with `Content-Disposition` (UTF-8 names, RFC 6266/8187), `ETag`, `Last-Modified`,
conditional requests (304/412) and byte ranges (206/416).

## Errors

Throw an `HttpException` (`Core\Http\Exception\*`: one class per common 4xx/5xx status, with the headers
the status requires, e.g. `Allow`, `WWW-Authenticate`, `Retry-After`). Errors are problem details (RFC 9457):
`application/problem+json`, `application/problem+xml`, HTML or text by `Accept`. Other exceptions are 500,
with details only in debug mode. Status codes: `Core\Http\Status` enum.

## Security

Middleware (`di.xml`, outermost first): `SecurityHeaders` (nosniff, frame-ancestors, Referrer-Policy, HSTS on https),
`Cors` (Fetch standard: preflight, credentials, exposed headers, `Vary: Origin`), `ErrorHandler`, `BodySizeLimit` (413),
`BodyParsing`, `MethodOverride` (`X-HTTP-Method-Override` / `_method` for POST), `Csrf`.

CSRF: signed double-submit cookie `XSRF-TOKEN` + header `X-XSRF-TOKEN` / `X-CSRF-Token` or field `_csrf`,
plus `Origin` / `Sec-Fetch-Site` checks for unsafe methods. Requests without cookies (token APIs) and
configured path prefixes (webhooks) are not checked. The token is the `csrf_token` request attribute.

## Console

```php
final class Hello implements CommandInterface
{
    public function run(Input $input, Output $output): int
    {
        $output->writeln('Hello, ' . $input->getOption('name', 'World') . '!');

        return self::SUCCESS;
    }
}
```

## Config merging

All `*di.xml` / `*routes.xml` files of `app/Application/etc` are validated against their XSD and merged.
Order: fewer dots in the file name first, then alphabetical; later files override earlier ones
(`di.xml` → `local.di.xml`).

| Element | Matched by | On match |
|---|---|---|
| `interface` | `name` | attributes overridden (`class`) |
| `instance` | `class` | attributes overridden, arguments merged by `name` |
| `argument` | `name` | value replaced as a whole |
| `callback` | — | always appended |
| `route`, `command` | `name` | attributes overridden (`method`, `path`, `action`), `query` merged by `name` |

Example `app/Application/etc/local.di.xml`:

```xml
<di>
    <instance class="Monolog\Logger">
        <argument name="name"><string>my-app</string></argument>
    </instance>
</di>
```

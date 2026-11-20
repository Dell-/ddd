# Requests

Actions receive a `Core\Http\Message\ServerRequestInterface`: the PSR-7 `ServerRequestInterface` with
helpers. Requests are immutable: `with*()` methods return a new request.

## Where the data is

| | Method | Example |
|---|---|---|
| Route parameters | `getAttribute('id')`, `getRouteParams()`, `getRouteName()` | `/items/{id}` |
| Query string | `getQueryParams()`, `getQueryParam('filter.status')` | `?filter[status]=new` |
| Body | `getParsedBody()`, `getBodyParam('address.city')` | JSON, form, multipart, XML, … |
| Raw body | `getBody()` (stream), `(string) $request->getBody()` | |
| Any input | `input('id')` — route parameter, then body, then query | |
| Headers | `getHeaderLine('Accept-Language')`, `hasHeader()`, `getHeader()` | case-insensitive |
| Cookies | `getCookieParams()`, `getCookieParam('theme')` | |
| Files | `getUploadedFile('avatar')`, `getUploadedFileList('photos')`, `getBodyAsFile()` | see [Files](files.md) |
| Server | `getServerParams()`, `getUri()`, `getMethod()`, `getProtocolVersion()` | |

Nested values are addressed with dots or brackets: `getQueryParam('filter.status')` and
`getQueryParam('filter[status]')` are the same. Missing values return the default (`null`).

```php
$page = (int) $request->getQueryParam('page', 1);
$email = $request->getBodyParam('user.email');
$id = $request->input('id');
```

## Request bodies

`BodyParsingMiddleware` parses the body by its `Content-Type` **for every method** — PHP itself only parses
form and multipart `POST`:

| Content-Type | Parsed body |
|---|---|
| `application/json`, any `…+json` (`application/merge-patch+json`, `application/vnd.api+json`, …) | array (objects as associative arrays); integers beyond `PHP_INT_MAX` as strings |
| `application/x-ndjson`, `application/jsonl`, `application/x-jsonlines`, `application/jsonlines`, `application/x-jsonl` | list of the values, one JSON text per line, empty lines ignored |
| `application/json-seq` (RFC 7464) | list of the values (records start with `RS`, 0x1E) |
| `application/x-www-form-urlencoded` | array, `a[b][]=1` nesting; names are kept (`a.b` stays `a.b`, PHP would make it `a_b`) |
| `multipart/form-data` | fields as array, files as uploaded files (also for `PUT`/`PATCH`) |
| `application/xml`, `text/xml`, any `…+xml` | `SimpleXMLElement`; a DOCTYPE is rejected (XXE) |
| anything else | not parsed: `getParsedBody()` is `null`, read `getBody()` or `getBodyAsFile()` |

Malformed bodies are **400** with a precise detail, e.g. `Invalid JSON on line 3: Syntax error.`; too many
records or parts **413**. A top-level JSON scalar (`"text"`, `42`) is rejected: PSR-7 allows only arrays and
objects as parsed body.

```
POST /records
Content-Type: application/x-ndjson

{"id":1}
{"id":2}
```

```php
$records = $request->getParsedBody();   // [['id' => 1], ['id' => 2]]
```

Parsers are configured in `di.xml` (`BodyParsingMiddleware`'s `parsers`); implement
`Core\Http\Body\BodyParserInterface` for another format.

## Content negotiation helpers

```php
$request->getMediaType();              // MediaType of Content-Type: ->essence, ->suffix, ->charset, ->isJson()
$request->getAccept()->negotiate(['application/json', 'text/csv']);   // the client's choice or null
$request->accepts('application/json'); // RFC 9110 §12.5.1 rules, q-values, wildcards
$request->getContentLength();          // ?int
```

## Other helpers

```php
$request->getMethodEnum();     // Core\Http\Method::Patch or null for non-standard methods
$request->isMethod(Method::Post);
$request->isSecure();          // https
$request->getOrigin();         // "https://example.com:8443"
```

## Creating requests

```php
$request = ServerRequestFactory::fromGlobals();      // what web/index.php does
$request = ServerRequestFactory::fromArrays($server, $query, $post, $cookies, $files, $body);
$request = new HttpFactory()->createServerRequest('GET', 'https://example.com/items');   // PSR-17
```

Proxy headers (`X-Forwarded-For`, `X-Forwarded-Proto`, `Forwarded`) are not trusted: the URI is what the
web server reports.

# Responses

Responses are PSR-7 `ResponseInterface` objects (`Core\Http\Message\Response`). Ready-made classes in
`Core\Http\Response` set the right headers:

| Class | Content-Type | Notes |
|---|---|---|
| `JsonResponse($data, $status, $headers, $flags, $mediaType)` | `application/json` | UTF-8, unescaped slashes and Unicode, `1.0` stays `1.0`; `$mediaType` for `+json` types |
| `JsonLinesResponse($records, …, $mediaType, $sequence)` | `application/x-ndjson` | one JSON text per line; `sequence: true` → `application/json-seq` (RFC 7464); takes any iterable (generators) |
| `HtmlResponse($html)` | `text/html; charset=utf-8` | |
| `TextResponse($text)` | `text/plain; charset=utf-8` | |
| `XmlResponse($xml, …, $mediaType)` | `application/xml` | a string, `DOMDocument`, `SimpleXMLElement` or data (encoded) |
| `EmptyResponse($status = 204)` | — | no body; also 205, 304, an empty 201/202 |
| `RedirectResponse($location, $status = 302)` | — | `Location`; 3xx only |
| `FileResponse($path, $filename, $disposition)` | detected | see [Files](files.md#downloads) |

```php
use Core\Http\Response\JsonResponse;
use Core\Http\Status;

return new JsonResponse(['id' => 42], Status::Created, ['Location' => '/items/42']);
```

## Status codes

`Core\Http\Status` is an enum of all registered status codes with their reason phrases; everything that
takes a status accepts `Status|int`.

```php
Status::NotFound->value;            // 404
Status::NotFound->reasonPhrase();   // "Not Found"
Status::reasonPhraseOf(299);        // "" (unregistered codes are allowed)
Status::TooManyRequests->isClientError();
Status::isWithoutContent(304);      // true: 1xx, 204, 304 never have a body
```

## Redirects

| Status | When |
|---|---|
| `301 Moved Permanently` / `308 Permanent Redirect` | the resource has moved for good (308 keeps the method and the body) |
| `302 Found` / `307 Temporary Redirect` | temporarily elsewhere (307 keeps the method and the body) |
| `303 See Other` | after a successful form POST: the browser fetches the result with GET |

```php
return new RedirectResponse('/items/42', Status::SeeOther);
return $this->redirect('/items/42');          // AbstractAction: 303 by default
```

## Headers

PSR-7 responses are immutable; header names are case-insensitive and validated (no CR/LF: no header
injection).

```php
$response = $response
    ->withHeader('Cache-Control', 'max-age=3600')
    ->withAddedHeader('Link', '</style.css>; rel=preload; as=style');
```

## Cookies

`Core\Http\Header\SetCookie` builds `Set-Cookie` values (RFC 6265bis) with secure defaults —
`Path=/`, `HttpOnly`, `SameSite=Lax`:

```php
use Core\Http\Header\SameSite;
use Core\Http\Header\SetCookie;

$cookie = new SetCookie('theme', 'dark')
    ->withMaxAge(86400 * 30)
    ->withSecure()
    ->withSameSite(SameSite::Strict);

return $response->withAddedHeader('Set-Cookie', (string) $cookie);
// theme=dark; Max-Age=2592000; Path=/; Secure; HttpOnly; SameSite=Strict

$response->withAddedHeader('Set-Cookie', (string) SetCookie::expire('theme'));   // delete
```

`SameSite=None` and `Partitioned` add `Secure` automatically (browsers require it). Values are
percent-encoded; PHP decodes them into `$_COOKIE`.

## Streaming large bodies

The body is a PSR-7 stream; the `Emitter` sends it in 64 KB chunks, so a file stream is never read into
memory at once:

```php
use Core\Http\Message\Response;
use Core\Http\Message\Stream;

return new Response(200, ['Content-Type' => 'text/csv'], Stream::fromFile('/var/export.csv'));
```

## HEAD and empty responses

The body is not sent for `HEAD` requests and for `1xx`, `204` and `304` responses (RFC 9110 §6.4.1); a
response without `Content-Type` is sent without one (PHP's default `text/html` is suppressed).

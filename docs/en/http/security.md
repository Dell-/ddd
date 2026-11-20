# Security

## Security headers

`SecurityHeadersMiddleware` adds to every response (unless the action set them):

| Header | Value | Against |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | MIME sniffing (Fetch) |
| `X-Frame-Options` | `DENY` | clickjacking (older browsers) |
| `Content-Security-Policy` | `frame-ancestors 'none'` | clickjacking |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | URL leaks |
| `Cross-Origin-Opener-Policy` | `same-origin` | cross-window attacks |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — **https only** | protocol downgrade (RFC 6797) |

```xml
<object class="Core\Http\Middleware\SecurityHeadersMiddleware">
    <argument name="headers">
        <array>
            <item name="Content-Security-Policy"><string>default-src 'self'; frame-ancestors 'none'</string></item>
            <item name="Permissions-Policy"><string>camera=(), microphone=()</string></item>
            <item name="X-Frame-Options"><string></string></item>  <!-- "" removes a default -->
        </array>
    </argument>
</object>
```

## CORS

`CorsMiddleware` implements the CORS protocol of the WHATWG Fetch standard.

```sh
APP_CORS_ORIGINS="https://app.example.com https://admin.example.com"
```

```xml
<object class="Core\Http\Middleware\CorsMiddleware">
    <argument name="allowedOrigins"><env name="APP_CORS_ORIGINS" default=""/></argument>
    <argument name="exposedHeaders"><array><item><string>Location</string></item></array></argument>
    <argument name="allowCredentials"><bool>true</bool></argument>
    <argument name="maxAge"><int>600</int></argument>
</object>
```

| Argument | Default | |
|---|---|---|
| `allowedOrigins` | none | exact origins, `*`, or regular expressions `#^https://[a-z]+\.example\.com$#`; a list or a string |
| `allowedMethods` | GET, HEAD, POST, PUT, PATCH, DELETE | |
| `allowedHeaders` | Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-XSRF-TOKEN | `*` = any (not with credentials) |
| `exposedHeaders` | none | response headers scripts may read |
| `allowCredentials` | `false` | cookies / authorization; then the origin is echoed instead of `*` |
| `maxAge` | 600 | preflight cache, seconds |

Behaviour:

- No `Origin`, or the request's own origin: not CORS, nothing added.
- **Preflight** (`OPTIONS` + `Origin` + `Access-Control-Request-Method`) is answered by the middleware:
  `204` with the `Access-Control-Allow-*` headers, or `403` (without CORS headers) if the origin, the method
  or a requested header is not allowed. CORS-safelisted headers (`Accept`, `Content-Type`, …) need no permission.
- **Actual requests** go to the application; allowed origins get `Access-Control-Allow-Origin` (and
  `-Allow-Credentials`, `-Expose-Headers`) — also on error responses. The server cannot block requests from
  other origins; it only tells the browser whether a script may read the response.
- Responses carry `Vary: Origin`.

```
OPTIONS /items/1
Origin: https://app.example.com
Access-Control-Request-Method: PUT
Access-Control-Request-Headers: content-type

HTTP/1.1 204 No Content
Access-Control-Allow-Origin: https://app.example.com
Access-Control-Allow-Methods: GET, HEAD, POST, PUT, PATCH, DELETE
Access-Control-Allow-Headers: content-type
Access-Control-Allow-Credentials: true
Access-Control-Max-Age: 600
Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers
```

## CSRF

Cross-site request forgery makes a victim's browser send a request with the victim's cookies.
`CsrfMiddleware` protects unsafe methods (`POST`, `PUT`, `PATCH`, `DELETE`) without server-side sessions
(OWASP CSRF Prevention Cheat Sheet):

1. **Origin checks.** `Sec-Fetch-Site: cross-site` is rejected; an `Origin` other than the request's own
   (and not in `trustedOrigins`) is rejected, including `Origin: null`.
2. **Signed double-submit cookie.** Responses set the cookie `XSRF-TOKEN` = random value + HMAC-SHA256 with
   `APP_SECRET` (`SameSite=Lax`, `Secure` on https, readable by scripts). Unsafe requests must send the same
   token in the `X-XSRF-TOKEN` or `X-CSRF-Token` header or the `_csrf` form field. Another site can neither
   read the cookie nor forge a valid signature.

Not checked: safe methods; requests **without any cookie** (no ambient credentials to abuse — token-based
API clients); paths in `except` (for example webhooks).

```
GET /                                   → Set-Cookie: XSRF-TOKEN=MpON2V….4wsDY6…; Path=/; SameSite=Lax
POST /items  Cookie: XSRF-TOKEN=…       → 403 {"detail":"Invalid or missing CSRF token."}
POST /items  Cookie: XSRF-TOKEN=…
             X-XSRF-TOKEN: MpON2V….4wsDY6…   → 201 Created
```

**JavaScript**: Angular's `HttpClient` and axios read `XSRF-TOKEN` and send `X-XSRF-TOKEN` automatically.
With `fetch`:

```js
const token = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/)?.[1];
await fetch('/items', {method: 'POST', headers: {'X-XSRF-TOKEN': decodeURIComponent(token), 'Content-Type': 'application/json'}, body: '{}'});
```

**HTML forms**: the token is the request attribute `csrf_token` (in templates: `request.getAttribute('csrf_token')`):

```html
<form method="post" action="/items">
    <input type="hidden" name="_csrf" value="{{ request.getAttribute('csrf_token') }}">
    ...
</form>
```

```xml
<object class="Core\Http\Middleware\CsrfMiddleware">
    <argument name="secret"><env name="APP_SECRET"/></argument>
    <argument name="except"><array><item><string>/webhooks/</string></item></array></argument>
    <argument name="trustedOrigins"><array><item><string>https://admin.example.com</string></item></array></argument>
</object>
```

`APP_SECRET` must be at least 32 characters, random and secret: `openssl rand -hex 32`.

## Other protections

| Threat | Protection |
|---|---|
| Header injection | header names and values are validated (no CR, LF, NUL) |
| XXE, entity expansion | `XmlParser` rejects DOCTYPE, no network access |
| Huge bodies | `BodySizeLimitMiddleware` (413); multipart and JSON Lines limits |
| Path traversal via file names | `ContentDisposition` and `UploadedFile` drop directories; `store()` uses random names |
| Trusting client media types | `UploadedFile::getMediaType()` detects the type from the content |
| Leaking internals | 500 responses without details unless `APP_DEBUG=1` |
| Open redirects | `RedirectResponse` sends the location you give: never pass user input unchecked |
| Method tunnelling to safe methods | method override only to `PUT`/`PATCH`/`DELETE` |

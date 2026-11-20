# Безпека

<a id="security-headers"></a>

## Заголовки безпеки

`SecurityHeadersMiddleware` додає до кожної відповіді (якщо їх не встановив action):

| Заголовок | Значення | Проти чого |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | MIME sniffing (Fetch) |
| `X-Frame-Options` | `DENY` | clickjacking (старіші браузери) |
| `Content-Security-Policy` | `frame-ancestors 'none'` | clickjacking |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | витоку URL |
| `Cross-Origin-Opener-Policy` | `same-origin` | атак між вікнами |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` — **лише https** | пониження протоколу (RFC 6797) |

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

<a id="cors"></a>

## CORS

`CorsMiddleware` реалізує протокол CORS зі стандарту WHATWG Fetch.

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

| Аргумент | За замовчуванням | |
|---|---|---|
| `allowedOrigins` | немає | точні origin, `*` або регулярні вирази `#^https://[a-z]+\.example\.com$#`; список або рядок |
| `allowedMethods` | GET, HEAD, POST, PUT, PATCH, DELETE | |
| `allowedHeaders` | Content-Type, Authorization, X-Requested-With, X-CSRF-Token, X-XSRF-TOKEN | `*` = будь-які (не разом із credentials) |
| `exposedHeaders` | немає | заголовки відповіді, які можуть читати скрипти |
| `allowCredentials` | `false` | cookies / авторизація; тоді замість `*` повертається origin запиту |
| `maxAge` | 600 | кеш preflight, секунди |

Поведінка:

- Немає `Origin`, або це власний origin запиту: це не CORS, нічого не додається.
- На **preflight** (`OPTIONS` + `Origin` + `Access-Control-Request-Method`) відповідає сам middleware:
  `204` із заголовками `Access-Control-Allow-*` або `403` (без заголовків CORS), якщо origin, метод
  чи запитаний заголовок не дозволені. CORS-safelisted заголовки (`Accept`, `Content-Type`, …) дозволу не потребують.
- **Фактичні запити** потрапляють до застосунку; дозволені origin отримують `Access-Control-Allow-Origin` (а також
  `-Allow-Credentials`, `-Expose-Headers`) — і у відповідях з помилками. Сервер не може заблокувати запити з
  інших origin; він лише повідомляє браузеру, чи може скрипт прочитати відповідь.
- Відповіді містять `Vary: Origin`.

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

<a id="csrf"></a>

## CSRF

Міжсайтове підроблення запиту (cross-site request forgery) змушує браузер жертви надіслати запит із cookies жертви.
`CsrfMiddleware` захищає небезпечні методи (`POST`, `PUT`, `PATCH`, `DELETE`) без серверних сесій
(OWASP CSRF Prevention Cheat Sheet):

1. **Перевірка origin.** `Sec-Fetch-Site: cross-site` відхиляється; `Origin`, відмінний від власного origin запиту
   (і не з `trustedOrigins`), відхиляється, зокрема `Origin: null`.
2. **Підписаний double-submit cookie.** Відповіді встановлюють cookie `XSRF-TOKEN` = випадкове значення + HMAC-SHA256 з
   `APP_SECRET` (`SameSite=Lax`, `Secure` на https, доступний скриптам). Небезпечні запити мають надсилати той самий
   токен у заголовку `X-XSRF-TOKEN` або `X-CSRF-Token`, або в полі форми `_csrf`. Інший сайт не може ні
   прочитати cookie, ні підробити чинний підпис.

Не перевіряються: безпечні методи; запити **без жодного cookie** (немає неявних облікових даних, якими можна
зловжити — токенні API-клієнти); шляхи з `except` (наприклад, вебхуки).

```
GET /                                   → Set-Cookie: XSRF-TOKEN=MpON2V….4wsDY6…; Path=/; SameSite=Lax
POST /items  Cookie: XSRF-TOKEN=…       → 403 {"detail":"Invalid or missing CSRF token."}
POST /items  Cookie: XSRF-TOKEN=…
             X-XSRF-TOKEN: MpON2V….4wsDY6…   → 201 Created
```

**JavaScript**: `HttpClient` в Angular та axios самі читають `XSRF-TOKEN` і надсилають `X-XSRF-TOKEN`.
З `fetch`:

```js
const token = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/)?.[1];
await fetch('/items', {method: 'POST', headers: {'X-XSRF-TOKEN': decodeURIComponent(token), 'Content-Type': 'application/json'}, body: '{}'});
```

**HTML-форми**: токен — це атрибут запиту `csrf_token` (у шаблонах: `request.getAttribute('csrf_token')`):

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

`APP_SECRET` має бути щонайменше 32 символи завдовжки, випадковим і таємним: `openssl rand -hex 32`.

## Інші захисти

| Загроза | Захист |
|---|---|
| Ін'єкція заголовків | імена та значення заголовків перевіряються (без CR, LF, NUL) |
| XXE, розширення сутностей | `XmlParser` відхиляє DOCTYPE, без доступу до мережі |
| Величезні тіла | `BodySizeLimitMiddleware` (413); обмеження multipart і JSON Lines |
| Path traversal через імена файлів | `ContentDisposition` і `UploadedFile` відкидають каталоги; `store()` використовує випадкові імена |
| Довіра до клієнтських медіатипів | `UploadedFile::getMediaType()` визначає тип за вмістом |
| Витік внутрішніх даних | відповіді 500 без подробиць, якщо не `APP_DEBUG=1` |
| Відкриті перенаправлення | `RedirectResponse` надсилає ту адресу, яку ви передали: ніколи не передавайте введення користувача без перевірки |
| Тунелювання методів до безпечних методів | перевизначення методу лише на `PUT`/`PATCH`/`DELETE` |

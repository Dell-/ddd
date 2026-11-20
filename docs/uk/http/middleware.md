# Middleware

HTTP-ядро (`Core\Http\Kernel`) — це конвеєр PSR-15: кожен middleware отримує запит і наступний
обробник, і може змінити запит, відповісти самостійно або змінити відповідь.

## Конвеєр за замовчуванням

Налаштовується в `di.xml`, від зовнішнього до внутрішнього:

| Middleware | Що робить |
|---|---|
| `SecurityHeadersMiddleware` | заголовки безпеки в кожній відповіді, див. [Безпека](security.md#security-headers) |
| `CorsMiddleware` | CORS: відповідає на preflight, додає заголовки до міждоменних відповідей |
| `ErrorHandlerMiddleware` | винятки → problem details, логування |
| `BodySizeLimitMiddleware` | 413 для тіл, більших за `APP_MAX_BODY_SIZE` |
| `BodyParsingMiddleware` | розбирає тіло за `Content-Type` |
| `MethodOverrideMiddleware` | `POST` + `_method` / `X-HTTP-Method-Override` → `PUT`/`PATCH`/`DELETE` |
| `CsrfMiddleware` | відхиляє підроблені міжсайтові запити, видає токен |
| *`RouteHandler`* | кінцевий обробник: маршрутизація та action |

Порядок має значення: заголовки безпеки та CORS додаються поза обробником помилок, тож відповіді з помилками
їх мають; тіло розбирається до перевизначення методу (він читає `_method`) і до CSRF (він читає `_csrf`).

## Написання middleware

```php
<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds a request id (from the client or new) to the request and the response.
 */
final readonly class RequestIdMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = $request->getHeaderLine('X-Request-Id') ?: bin2hex(random_bytes(8));

        $response = $handler->handle($request->withAttribute('request_id', $id));

        return $response->withHeader('X-Request-Id', $id);
    }
}
```

Зареєструйте його в `di.xml` на потрібній позиції:

```xml
<instance class="Core\Http\Kernel" shared="true">
    <argument name="handler"><object class="Core\Http\RouteHandler"/></argument>
    <argument name="middleware">
        <array>
            <item><object class="Core\Http\Middleware\SecurityHeadersMiddleware"/></item>
            <item><object class="App\Http\RequestIdMiddleware"/></item>
            ...
        </array>
    </argument>
</instance>
```

Залежності middleware підставляються автоматично (autowiring). Будь-який PSR-15 middleware зі сторонніх бібліотек
працює так само.

## Раннє завершення відповіддю

```php
if ($this->maintenance) {
    throw new ServiceUnavailableException(retryAfter: 600);   // rendered by ErrorHandlerMiddleware
}
```

Middleware, розміщений *поза* `ErrorHandlerMiddleware`, має повертати відповіді, а не викидати винятки.

## Налаштування вбудованих middleware

| Middleware | Аргументи |
|---|---|
| `SecurityHeadersMiddleware` | `headers` (додати або `""`, щоб прибрати типовий), `hsts` (значення або `null`) |
| `CorsMiddleware` | `allowedOrigins`, `allowedMethods`, `allowedHeaders`, `exposedHeaders`, `allowCredentials`, `maxAge` |
| `ErrorHandlerMiddleware` | `debug` |
| `BodySizeLimitMiddleware` | `maxBytes` |
| `BodyParsingMiddleware` | `parsers` (список `BodyParserInterface`) |
| `MethodOverrideMiddleware` | `allowed` (список `Method`) |
| `CsrfMiddleware` | `secret`, `cookieName`, `headerNames`, `fieldName`, `except`, `trustedOrigins`, `skipWithoutCookies`, `sameSite` |

## Використання ядра поза web/index.php

```php
$kernel = \Bootstrap::createContainer()->get(Kernel::class);
$response = $kernel->handle($psr7ServerRequest);   // e.g. in tests or another server runtime
```

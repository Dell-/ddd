# Middleware

The HTTP kernel (`Core\Http\Kernel`) is a PSR-15 pipeline: each middleware gets the request and the next
handler, and may change the request, answer itself or change the response.

## The default pipeline

Configured in `di.xml`, outermost first:

| Middleware | Does |
|---|---|
| `SecurityHeadersMiddleware` | security headers on every response, see [Security](security.md#security-headers) |
| `CorsMiddleware` | CORS: answers preflights, adds headers to cross-origin responses |
| `ErrorHandlerMiddleware` | exceptions → problem details, logging |
| `BodySizeLimitMiddleware` | 413 for bodies over `APP_MAX_BODY_SIZE` |
| `BodyParsingMiddleware` | parses the body by `Content-Type` |
| `MethodOverrideMiddleware` | `POST` + `_method` / `X-HTTP-Method-Override` → `PUT`/`PATCH`/`DELETE` |
| `CsrfMiddleware` | rejects forged cross-site requests, issues the token |
| *`RouteHandler`* | the final handler: routing and the action |

The order matters: security and CORS headers are added outside the error handler, so errors have them;
the body is parsed before method override (it reads `_method`) and before CSRF (it reads `_csrf`).

## Writing middleware

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

Register it in `di.xml` at the right position:

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

Middleware dependencies are autowired. Any PSR-15 middleware from other libraries works the same way.

## Answering early

```php
if ($this->maintenance) {
    throw new ServiceUnavailableException(retryAfter: 600);   // rendered by ErrorHandlerMiddleware
}
```

Middleware placed *outside* `ErrorHandlerMiddleware` must return responses instead of throwing.

## Configuring the built-in middleware

| Middleware | Arguments |
|---|---|
| `SecurityHeadersMiddleware` | `headers` (add, or `""` to remove a default), `hsts` (value or `null`) |
| `CorsMiddleware` | `allowedOrigins`, `allowedMethods`, `allowedHeaders`, `exposedHeaders`, `allowCredentials`, `maxAge` |
| `ErrorHandlerMiddleware` | `debug` |
| `BodySizeLimitMiddleware` | `maxBytes` |
| `BodyParsingMiddleware` | `parsers` (list of `BodyParserInterface`) |
| `MethodOverrideMiddleware` | `allowed` (list of `Method`) |
| `CsrfMiddleware` | `secret`, `cookieName`, `headerNames`, `fieldName`, `except`, `trustedOrigins`, `skipWithoutCookies`, `sameSite` |

## Using the kernel outside web/index.php

```php
$kernel = \Bootstrap::createContainer()->get(Kernel::class);
$response = $kernel->handle($psr7ServerRequest);   // e.g. in tests or another server runtime
```

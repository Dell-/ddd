# Помилки

Викиньте HTTP-виняток будь-де: в action, у сервісі чи в middleware; `ErrorHandlerMiddleware` перетворить його
на відповідь.

```php
throw new NotFoundException('Item 42 not found.');
throw new UnprocessableContentException(['email' => 'The email is invalid.']);
throw new TooManyRequestsException(retryAfter: 60);
throw new UnauthorizedException('Bearer realm="api", error="invalid_token"');
```

## Опис проблеми (problem details, RFC 9457)

Помилки повертаються як problem details, у форматі, який приймає клієнт:

| Accept | Content-Type |
|---|---|
| JSON (`application/json`, `*/*`, відсутній) | `application/problem+json` |
| XML (`application/xml`) | `application/problem+xml` (RFC 9457, додаток B) |
| `text/html` | HTML-сторінка |
| `text/plain` | `detail` як текст |

```
POST /items
Content-Type: application/json

{"title":"x"}

HTTP/1.1 422 Unprocessable Content
Content-Type: application/problem+json
Vary: Accept

{"type":"about:blank","title":"Unprocessable Content","status":422,
 "detail":"The content contains invalid values.","errors":{"name":"The name is required."}}
```

```
GET /nope
Accept: application/xml

HTTP/1.1 404 Not Found
Content-Type: application/problem+xml

<problem xmlns="urn:ietf:rfc:7807"><type>about:blank</type><title>Not Found</title><status>404</status>
<detail>No route for "GET /nope".</detail></problem>
```

| Поле | Звідки береться |
|---|---|
| `type` | аргумент `$type`, за замовчуванням `about:blank` (код статусу вже все пояснює) |
| `title` | аргумент `$title`, за замовчуванням — reason phrase |
| `status` | код статусу |
| `detail` | повідомлення — **показується клієнту**: без внутрішньої інформації |
| розширення | `$extensions` (`errors`, `available`, …); не можуть замінити стандартні поля |

## Класи винятків

`Core\Http\Exception\HttpException` — базовий клас (`ClientErrorException` для 4xx, `ServerErrorException` для
5xx). Для кожного поширеного статусу є окремий клас; ті, чий статус вимагає заголовка, приймають його
як аргумент:

| Виняток | Заголовок / розширення |
|---|---|
| `UnauthorizedException($challenge = 'Bearer')` | `WWW-Authenticate` (вимагається RFC 9110 §15.5.2) |
| `ProxyAuthenticationRequiredException($challenge)` | `Proxy-Authenticate` |
| `MethodNotAllowedException($allowed)` | `Allow` |
| `NotAcceptableException($available)` | розширення `available` |
| `UnsupportedMediaTypeException($supported)` | `Accept` |
| `RangeNotSatisfiableException($length)` | `Content-Range: bytes */length` |
| `UnprocessableContentException($errors)` | розширення `errors` |
| `TooManyRequestsException($retryAfter)`, `ServiceUnavailableException($retryAfter)` | `Retry-After` (секунди або дата) |
| `UpgradeRequiredException($upgrade)` | `Upgrade`, `Connection: Upgrade` |

Повний список — у [довіднику](../reference/status-codes.md).

## Власні типи проблем

```php
throw new HttpException(
    Status::Forbidden,
    'Your balance is 30, the item costs 50.',
    type: 'https://example.com/problems/out-of-credit',
    title: 'You do not have enough credit.',
    extensions: ['balance' => 30, 'accounts' => ['/account/12345']],
);
```

Або власний клас:

```php
final class OutOfCreditException extends ClientErrorException
{
    public function __construct(int $balance)
    {
        parent::__construct(Status::Forbidden, sprintf('Your balance is %d.', $balance),
            type: 'https://example.com/problems/out-of-credit', extensions: ['balance' => $balance]);
    }
}
```

## Неочікувані винятки

Будь-який інший виняток дає **500 Internal Server Error** без подробиць; він логується разом зі стеком викликів
(рівень `error`). З `APP_DEBUG=1` поле `detail` містить клас, повідомлення та місце виникнення.
`HttpException` логуються як `notice` (4xx) або `error` (5xx).

## Помилки, CORS і заголовки безпеки

`SecurityHeadersMiddleware` і `CorsMiddleware` розташовані поза `ErrorHandlerMiddleware`, тому відповіді з
помилками мають і заголовки безпеки, і заголовки CORS: скрипт у браузері може прочитати problem details
міждоменного запиту.

## Консоль

У консолі невідома команда виводить `Command "x" not found.` у STDERR; інші винятки виводять свій
клас і повідомлення (увесь стек викликів — з `APP_DEBUG=1`); код завершення — 1.

# Відповіді

Відповіді — це об'єкти PSR-7 `ResponseInterface` (`Core\Http\Message\Response`). Готові класи в
`Core\Http\Response` встановлюють правильні заголовки:

| Клас | Content-Type | Примітки |
|---|---|---|
| `JsonResponse($data, $status, $headers, $flags, $mediaType)` | `application/json` | UTF-8, неекрановані слеші та Unicode, `1.0` залишається `1.0`; `$mediaType` для типів `+json` |
| `JsonLinesResponse($records, …, $mediaType, $sequence)` | `application/x-ndjson` | один JSON-текст на рядок; `sequence: true` → `application/json-seq` (RFC 7464); приймає будь-який iterable (генератори) |
| `HtmlResponse($html)` | `text/html; charset=utf-8` | |
| `TextResponse($text)` | `text/plain; charset=utf-8` | |
| `XmlResponse($xml, …, $mediaType)` | `application/xml` | рядок, `DOMDocument`, `SimpleXMLElement` або дані (кодуються) |
| `EmptyResponse($status = 204)` | — | без тіла; також 205, 304, порожні 201/202 |
| `RedirectResponse($location, $status = 302)` | — | `Location`; лише 3xx |
| `FileResponse($path, $filename, $disposition)` | визначається автоматично | див. [Файли](files.md#downloads) |

```php
use Core\Http\Response\JsonResponse;
use Core\Http\Status;

return new JsonResponse(['id' => 42], Status::Created, ['Location' => '/items/42']);
```

## Коди статусу

`Core\Http\Status` — це enum усіх зареєстрованих кодів статусу з їхніми reason phrase; усе, що
приймає статус, приймає `Status|int`.

```php
Status::NotFound->value;            // 404
Status::NotFound->reasonPhrase();   // "Not Found"
Status::reasonPhraseOf(299);        // "" (unregistered codes are allowed)
Status::TooManyRequests->isClientError();
Status::isWithoutContent(304);      // true: 1xx, 204, 304 never have a body
```

## Перенаправлення

| Статус | Коли |
|---|---|
| `301 Moved Permanently` / `308 Permanent Redirect` | ресурс переміщено назавжди (308 зберігає метод і тіло) |
| `302 Found` / `307 Temporary Redirect` | тимчасово розташований в іншому місці (307 зберігає метод і тіло) |
| `303 See Other` | після успішного POST форми: браузер отримує результат через GET |

```php
return new RedirectResponse('/items/42', Status::SeeOther);
return $this->redirect('/items/42');          // AbstractAction: 303 by default
```

## Заголовки

Відповіді PSR-7 незмінні; назви заголовків нечутливі до регістру й валідуються (без CR/LF: жодних
ін'єкцій заголовків).

```php
$response = $response
    ->withHeader('Cache-Control', 'max-age=3600')
    ->withAddedHeader('Link', '</style.css>; rel=preload; as=style');
```

## Cookies

`Core\Http\Header\SetCookie` формує значення `Set-Cookie` (RFC 6265bis) із безпечними типовими налаштуваннями —
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

`SameSite=None` і `Partitioned` автоматично додають `Secure` (цього вимагають браузери). Значення
кодуються percent-encoding; PHP декодує їх у `$_COOKIE`.

## Потокова передача великих тіл

Тіло — це потік PSR-7; `Emitter` надсилає його частинами по 64 КБ, тож потік файлу ніколи не
зчитується в пам'ять повністю:

```php
use Core\Http\Message\Response;
use Core\Http\Message\Stream;

return new Response(200, ['Content-Type' => 'text/csv'], Stream::fromFile('/var/export.csv'));
```

## HEAD і порожні відповіді

Тіло не надсилається для запитів `HEAD` і для відповідей `1xx`, `204` та `304` (RFC 9110 §6.4.1);
відповідь без `Content-Type` надсилається без нього (типовий `text/html` у PHP придушується).

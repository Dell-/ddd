# Стандарти

Фреймворк реалізує ці специфікації; код посилається на них у своїх коментарях.

## PHP-FIG

| Стандарт | Реалізація |
|---|---|
| PSR-3 Logger | `Psr\Log\LoggerInterface` (Monolog) |
| PSR-4 Autoloading | `Core\`, `Application\`, `Tests\` |
| PSR-7 HTTP messages | `Core\Http\Message`: `Request`, `ServerRequest`, `Response`, `Stream`, `Uri`, `UploadedFile` |
| PSR-15 HTTP handlers | `Kernel`, `RouteHandler`, middleware, PSR-15 actions |
| PSR-17 HTTP factories | `Core\Http\Message\HttpFactory` (усі шість фабрик) |

## IETF і WHATWG

| Специфікація | Де |
|---|---|
| RFC 9110 HTTP Semantics | методи (`Method`), коди статусів (`Status`), узгодження вмісту (`Accept`, `ViewRenderer`), умовні запити та діапазони (`FileResponse`), `Allow`/405, HEAD, OPTIONS, перенаправлення |
| RFC 9112 HTTP/1.1 | request target в absolute-form (`ServerRequestFactory`) |
| RFC 3986 URI | `Uri` |
| RFC 9457 Problem Details | `ProblemDetails`, `ProblemRenderer` (JSON і XML) |
| RFC 8259 JSON, RFC 6839 `+json` | `JsonParser`, `JsonResponse` |
| RFC 7464 JSON Text Sequences | `JsonSeqParser`, `JsonLinesResponse(sequence: true)` |
| JSON Lines, NDJSON | `JsonLinesParser`, `JsonLinesResponse` |
| RFC 7303 XML media types | `XmlParser`, `XmlRenderer` |
| RFC 7578 multipart/form-data, RFC 2046 | `MultipartParser` |
| RFC 6266 Content-Disposition, RFC 8187 | `ContentDisposition` |
| RFC 6265bis Cookies | `SetCookie`, `SameSite` |
| RFC 6797 HSTS | `SecurityHeadersMiddleware` |
| RFC 2397 data URIs, RFC 4648 base64 | `UploadedFile::fromDataUri()`, `fromBase64()` |
| RFC 6585 (429, 428, 431, 511), RFC 4918 (422–424, 507) | `Status`, винятки |
| WHATWG Fetch — протокол CORS | `CorsMiddleware` |
| WHATWG URL — application/x-www-form-urlencoded | `FormData` |
| OWASP CSRF Prevention, HTTP Headers, XXE Prevention | `CsrfMiddleware`, `SecurityHeadersMiddleware`, `XmlParser` |

## Відомі відхилення

- Quoted strings у `Content-Disposition`: екрануваннями є лише `\"` і `\\`, бо браузери надсилають шляхи Windows
  (`C:\dir\file`) без екранування в іменах файлів multipart (WHATWG HTML).
- Скалярне JSON-тіло верхнього рівня відхиляється (розібрані тіла в PSR-7 — це масиви або об'єкти).
- Запити з кількома діапазонами байтів отримують усе представлення (дозволено RFC 9110 §14.2).

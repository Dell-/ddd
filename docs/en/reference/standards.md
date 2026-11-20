# Standards

The framework implements these specifications; the code refers to them in its comments.

## PHP-FIG

| Standard | Implementation |
|---|---|
| PSR-3 Logger | `Psr\Log\LoggerInterface` (Monolog) |
| PSR-4 Autoloading | `Core\`, `Application\`, `Tests\` |
| PSR-7 HTTP messages | `Core\Http\Message`: `Request`, `ServerRequest`, `Response`, `Stream`, `Uri`, `UploadedFile` |
| PSR-15 HTTP handlers | `Kernel`, `RouteHandler`, middleware, PSR-15 actions |
| PSR-17 HTTP factories | `Core\Http\Message\HttpFactory` (all six factories) |

## IETF and WHATWG

| Specification | Where |
|---|---|
| RFC 9110 HTTP Semantics | methods (`Method`), status codes (`Status`), content negotiation (`Accept`, `ViewRenderer`), conditional requests and ranges (`FileResponse`), `Allow`/405, HEAD, OPTIONS, redirects |
| RFC 9112 HTTP/1.1 | absolute-form request targets (`ServerRequestFactory`) |
| RFC 3986 URI | `Uri` |
| RFC 9457 Problem Details | `ProblemDetails`, `ProblemRenderer` (JSON and XML) |
| RFC 8259 JSON, RFC 6839 `+json` | `JsonParser`, `JsonResponse` |
| RFC 7464 JSON Text Sequences | `JsonSeqParser`, `JsonLinesResponse(sequence: true)` |
| JSON Lines, NDJSON | `JsonLinesParser`, `JsonLinesResponse` |
| RFC 7303 XML media types | `XmlParser`, `XmlRenderer` |
| RFC 7578 multipart/form-data, RFC 2046 | `MultipartParser` |
| RFC 6266 Content-Disposition, RFC 8187 | `ContentDisposition` |
| RFC 6265bis Cookies | `SetCookie`, `SameSite` |
| RFC 6797 HSTS | `SecurityHeadersMiddleware` |
| RFC 2397 data URIs, RFC 4648 base64 | `UploadedFile::fromDataUri()`, `fromBase64()` |
| RFC 6585 (429, 428, 431, 511), RFC 4918 (422–424, 507) | `Status`, exceptions |
| WHATWG Fetch — CORS protocol | `CorsMiddleware` |
| WHATWG URL — application/x-www-form-urlencoded | `FormData` |
| OWASP CSRF Prevention, HTTP Headers, XXE Prevention | `CsrfMiddleware`, `SecurityHeadersMiddleware`, `XmlParser` |

## Known deviations

- `Content-Disposition` quoted strings: only `\"` and `\\` are escapes, because browsers send Windows paths
  (`C:\dir\file`) unescaped in multipart file names (WHATWG HTML).
- A top-level JSON scalar body is rejected (PSR-7 parsed bodies are arrays or objects).
- Requests with several byte ranges get the whole representation (allowed by RFC 9110 §14.2).

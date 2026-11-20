# Коди статусів і винятки

`Core\Http\Status` (enum) містить усі коди з IANA HTTP Status Code Registry. Для кодів помилок є
клас винятку в `Core\Http\Exception` (усі успадковують `HttpException`; 4xx — `ClientErrorException`,
5xx — `ServerErrorException`). Прості винятки приймають `(?string $detail = null, ?\Throwable $previous = null,
array $headers = [])`.

| Код | Case в `Status` | Виняток | Аргументи / заголовки |
|---|---|---|---|
| 400 | `BadRequest` | `BadRequestException` | |
| 401 | `Unauthorized` | `UnauthorizedException` | `$challenge` → `WWW-Authenticate` |
| 402 | `PaymentRequired` | `PaymentRequiredException` | |
| 403 | `Forbidden` | `ForbiddenException` | |
| 404 | `NotFound` | `NotFoundException` | |
| 405 | `MethodNotAllowed` | `MethodNotAllowedException` | `$allowed` → `Allow` |
| 406 | `NotAcceptable` | `NotAcceptableException` | `$available` → extension `available` |
| 407 | `ProxyAuthenticationRequired` | `ProxyAuthenticationRequiredException` | `$challenge` → `Proxy-Authenticate` |
| 408 | `RequestTimeout` | `RequestTimeoutException` | |
| 409 | `Conflict` | `ConflictException` | |
| 410 | `Gone` | `GoneException` | |
| 411 | `LengthRequired` | `LengthRequiredException` | |
| 412 | `PreconditionFailed` | `PreconditionFailedException` | |
| 413 | `ContentTooLarge` | `ContentTooLargeException` | |
| 414 | `UriTooLong` | `UriTooLongException` | |
| 415 | `UnsupportedMediaType` | `UnsupportedMediaTypeException` | `$supported` → `Accept` |
| 416 | `RangeNotSatisfiable` | `RangeNotSatisfiableException` | `$completeLength` → `Content-Range` |
| 417 | `ExpectationFailed` | `ExpectationFailedException` | |
| 421 | `MisdirectedRequest` | `MisdirectedRequestException` | |
| 422 | `UnprocessableContent` | `UnprocessableContentException` | `$errors` → extension `errors` |
| 423 | `Locked` | `LockedException` | |
| 424 | `FailedDependency` | `FailedDependencyException` | |
| 425 | `TooEarly` | `TooEarlyException` | |
| 426 | `UpgradeRequired` | `UpgradeRequiredException` | `$upgrade` → `Upgrade`, `Connection` |
| 428 | `PreconditionRequired` | `PreconditionRequiredException` | |
| 429 | `TooManyRequests` | `TooManyRequestsException` | `$retryAfter` → `Retry-After` |
| 431 | `RequestHeaderFieldsTooLarge` | `RequestHeaderFieldsTooLargeException` | |
| 451 | `UnavailableForLegalReasons` | `UnavailableForLegalReasonsException` | |
| 500 | `InternalServerError` | `InternalServerErrorException` | |
| 501 | `NotImplemented` | `NotImplementedException` | |
| 502 | `BadGateway` | `BadGatewayException` | |
| 503 | `ServiceUnavailable` | `ServiceUnavailableException` | `$retryAfter` → `Retry-After` |
| 504 | `GatewayTimeout` | `GatewayTimeoutException` | |
| 505 | `HttpVersionNotSupported` | `HttpVersionNotSupportedException` | |
| 507 | `InsufficientStorage` | `InsufficientStorageException` | |
| 508 | `LoopDetected` | `LoopDetectedException` | |
| 511 | `NetworkAuthenticationRequired` | `NetworkAuthenticationRequiredException` | |

Інші коди (`506 VariantAlsoNegotiates`, будь-який незареєстрований 4xx/5xx): `new HttpException($code, $detail)`.

Інформаційні коди, коди успіху та перенаправлення: `Continue`, `SwitchingProtocols`, `Processing`, `EarlyHints`,
`Ok`, `Created`, `Accepted`, `NonAuthoritativeInformation`, `NoContent`, `ResetContent`, `PartialContent`,
`MultiStatus`, `AlreadyReported`, `ImUsed`, `MultipleChoices`, `MovedPermanently`, `Found`, `SeeOther`,
`NotModified`, `UseProxy`, `TemporaryRedirect`, `PermanentRedirect`.

## Методи

`Core\Http\Method` (enum): `Get`, `Head`, `Post`, `Put`, `Patch`, `Delete`, `Options`, `Trace`, `Connect`
з `isSafe()` (RFC 9110 §9.2.1), `isIdempotent()` (§9.2.2), `isCacheable()` (§9.2.3) та
`Method::fromName('patch')`.

# Errors

Throw an HTTP exception anywhere in an action, a service or a middleware; `ErrorHandlerMiddleware` turns it
into a response.

```php
throw new NotFoundException('Item 42 not found.');
throw new UnprocessableContentException(['email' => 'The email is invalid.']);
throw new TooManyRequestsException(retryAfter: 60);
throw new UnauthorizedException('Bearer realm="api", error="invalid_token"');
```

## Problem details (RFC 9457)

Errors are problem details, in the format the client accepts:

| Accept | Content-Type |
|---|---|
| JSON (`application/json`, `*/*`, none) | `application/problem+json` |
| XML (`application/xml`) | `application/problem+xml` (RFC 9457 Appendix B) |
| `text/html` | an HTML page |
| `text/plain` | the detail as text |

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

| Member | From |
|---|---|
| `type` | `$type` argument, `about:blank` by default (the status code says it all) |
| `title` | `$title` argument, the reason phrase by default |
| `status` | the status code |
| `detail` | the message — **shown to the client**: no internal information |
| extensions | `$extensions` (`errors`, `available`, …); cannot replace the standard members |

## Exception classes

`Core\Http\Exception\HttpException` is the base (`ClientErrorException` for 4xx, `ServerErrorException` for
5xx). There is one class per common status; those whose status requires a header take it as an argument:

| Exception | Header / extension |
|---|---|
| `UnauthorizedException($challenge = 'Bearer')` | `WWW-Authenticate` (required by RFC 9110 §15.5.2) |
| `ProxyAuthenticationRequiredException($challenge)` | `Proxy-Authenticate` |
| `MethodNotAllowedException($allowed)` | `Allow` |
| `NotAcceptableException($available)` | extension `available` |
| `UnsupportedMediaTypeException($supported)` | `Accept` |
| `RangeNotSatisfiableException($length)` | `Content-Range: bytes */length` |
| `UnprocessableContentException($errors)` | extension `errors` |
| `TooManyRequestsException($retryAfter)`, `ServiceUnavailableException($retryAfter)` | `Retry-After` (seconds or a date) |
| `UpgradeRequiredException($upgrade)` | `Upgrade`, `Connection: Upgrade` |

The full list is in the [reference](../reference/status-codes.md).

## Custom problem types

```php
throw new HttpException(
    Status::Forbidden,
    'Your balance is 30, the item costs 50.',
    type: 'https://example.com/problems/out-of-credit',
    title: 'You do not have enough credit.',
    extensions: ['balance' => 30, 'accounts' => ['/account/12345']],
);
```

Or a class of your own:

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

## Unexpected exceptions

Any other exception is a **500 Internal Server Error** without details; it is logged with its trace
(`error` level). With `APP_DEBUG=1` the detail contains the class, the message and the location.
`HttpException`s are logged as `notice` (4xx) or `error` (5xx).

## Errors and CORS / security headers

`SecurityHeadersMiddleware` and `CorsMiddleware` are outside `ErrorHandlerMiddleware`, so error responses
have the security headers and the CORS headers: a browser script can read the problem details of a
cross-origin request.

## Console

In the console an unknown command prints `Command "x" not found.` to STDERR; other exceptions print their
class and message (the whole trace with `APP_DEBUG=1`); the exit code is 1.

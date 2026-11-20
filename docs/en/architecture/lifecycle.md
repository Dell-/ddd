# Request lifecycle

## HTTP

```
web/index.php
  └─ Bootstrap::createApplication()          container from APP_ETC/di.xml
       └─ Application\Service::run()
            ├─ ServerRequestFactory::fromGlobals()      PSR-7 ServerRequest
            ├─ Kernel::handle($request)                 PSR-15 pipeline (di.xml)
            │    ├─ SecurityHeadersMiddleware
            │    ├─ CorsMiddleware                      preflight answered here
            │    ├─ ErrorHandlerMiddleware              exceptions → problem details
            │    ├─ BodySizeLimitMiddleware             413
            │    ├─ BodyParsingMiddleware               JSON, NDJSON, form, multipart, XML …
            │    ├─ MethodOverrideMiddleware            POST + _method → PUT/PATCH/DELETE
            │    ├─ CsrfMiddleware                      403 for forged requests
            │    └─ RouteHandler
            │         ├─ Router::match()                404 / 405 / 400 / trailing slash policy
            │         ├─ container->create(action)      dependencies autowired
            │         ├─ action($request)
            │         ├─ Responder::respond()           View / data → negotiated response
            │         └─ FileResponse::prepare()        304 / 412 / 206 / 416
            └─ Emitter::emit($response)                 status, headers, body (none for HEAD)
```

1. **Bootstrap.** `app/Bootstrap.php` defines `BP` (the `app/` directory), `APP_ETC` (configuration directory,
   `APP_ETC_DIR`), `APP_DEBUG` and loads the Composer autoloader. `Bootstrap::createContainer()` reads and
   validates every `*di.xml` of the configuration directory.
2. **The request.** `ServerRequestFactory::fromGlobals()` builds the PSR-7 request from `$_SERVER`, `$_GET`,
   `$_POST`, `$_COOKIE`, `$_FILES` and `php://input`. Only what PHP parses itself (form and multipart POST)
   becomes the parsed body here; everything else is parsed by `BodyParsingMiddleware`.
3. **The pipeline.** `Kernel` runs the middleware from `di.xml` outermost first. Each middleware may answer
   itself (CORS preflight, errors) or call the next one. `SecurityHeaders` and `Cors` are outside
   `ErrorHandler`, so error responses get their headers too.
4. **Routing.** `RouteHandler` asks the `Router` for the route. The route parameters become request
   attributes, declared query parameters get their defaults. See [Routing](../http/routing.md).
5. **The action** is created by the container per request (so it can have constructor dependencies) and
   called. A PSR-15 handler returns a response; an `ActionInterface` may return a `View`, data, a string or
   `null` and the `Responder` turns it into a response. See [Actions](../http/actions.md).
6. **Emitting.** `Emitter` sends the status line, the headers (several `Set-Cookie` lines) and the body in
   chunks; no body for `HEAD`, `1xx`, `204` and `304`.

Any exception between 3 and 5 reaches `ErrorHandlerMiddleware`: an `HttpException` becomes its status,
anything else a 500. See [Errors](../http/errors.md).

## Console

```
bin/console hello --name=World
  └─ Application\Service::run()               PHP_SAPI === 'cli'
       └─ Core\Console\Application::run(Input::fromArgv($argv))
            ├─ Router::matchCommand('hello')     <command name="hello" .../> in routes.xml
            └─ container->create(command)->run($input, $output)   → exit code
```

See [Console](../console.md).

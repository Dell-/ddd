# Життєвий цикл запиту

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

1. **Bootstrap.** `app/Bootstrap.php` визначає `BP` (каталог `app/`), `APP_ETC` (каталог конфігурації,
   `APP_ETC_DIR`), `APP_DEBUG` і підключає автозавантажувач Composer. `Bootstrap::createContainer()` читає та
   валідує кожен `*di.xml` із каталогу конфігурації.
2. **Запит.** `ServerRequestFactory::fromGlobals()` будує запит PSR-7 з `$_SERVER`, `$_GET`,
   `$_POST`, `$_COOKIE`, `$_FILES` та `php://input`. Розібраним тілом тут стає лише те, що PHP розбирає сам
   (form і multipart POST); усе інше розбирає `BodyParsingMiddleware`.
3. **Конвеєр.** `Kernel` запускає middleware з `di.xml`, починаючи із зовнішнього. Кожен middleware може
   відповісти сам (CORS preflight, помилки) або викликати наступний. `SecurityHeaders` і `Cors` розташовані зовні
   від `ErrorHandler`, тож відповіді з помилками також отримують свої заголовки.
4. **Маршрутизація.** `RouteHandler` просить у `Router` маршрут. Параметри маршруту стають атрибутами
   запиту, а оголошені параметри запиту отримують свої значення за замовчуванням. Див. [Маршрутизація](../http/routing.md).
5. **Action** створюється контейнером для кожного запиту (тож може мати залежності в конструкторі) і
   викликається. Обробник PSR-15 повертає відповідь; `ActionInterface` може повернути `View`, дані, рядок або
   `null`, а `Responder` перетворює це на відповідь. Див. [Actions](../http/actions.md).
6. **Відправлення.** `Emitter` надсилає рядок статусу, заголовки (кілька рядків `Set-Cookie`) і тіло
   частинами; тіла немає для `HEAD`, `1xx`, `204` і `304`.

Будь-який виняток між кроками 3 і 5 потрапляє до `ErrorHandlerMiddleware`: `HttpException` стає відповіддю зі
своїм статусом, будь-що інше — відповіддю 500. Див. [Помилки](../http/errors.md).

## Консоль

```
bin/console hello --name=World
  └─ Application\Service::run()               PHP_SAPI === 'cli'
       └─ Core\Console\Application::run(Input::fromArgv($argv))
            ├─ Router::matchCommand('hello')     <command name="hello" .../> in routes.xml
            └─ container->create(command)->run($input, $output)   → exit code
```

Див. [Консоль](../console.md).

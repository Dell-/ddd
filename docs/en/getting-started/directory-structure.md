# Directory structure

```
app/
├── Bootstrap.php            constants (BP, APP_ETC, APP_DEBUG) and the container factory
├── Application/             your application
│   ├── Action/Http/         HTTP actions
│   ├── Action/Cli/          console commands
│   ├── etc/                 configuration: di.xml, routes.xml (+ local.*.xml overrides)
│   ├── runtime/             logs (not in git)
│   └── Service.php          entry service: the console in the CLI, the HTTP kernel otherwise
└── Core/                    the framework
    ├── Config/              XML configuration reader
    ├── Console/             Input, Output, CommandInterface, Application
    ├── Di/                  container, di.xml converter, argument types, di.xsd
    ├── Filesystem/          files, directories, iterators, XML merger
    ├── Http/
    │   ├── Action/          ActionInterface, AbstractAction
    │   ├── Body/            request body parsers
    │   ├── Exception/       HttpException and one class per common 4xx/5xx status
    │   ├── Header/          MediaType, Accept, ContentDisposition, SetCookie, ...
    │   ├── Message/         PSR-7 messages, PSR-17 factories, UploadedFile
    │   ├── Middleware/      PSR-15 middleware
    │   ├── Response/        ready-made responses
    │   ├── View/            View, renderers, problem details
    │   ├── Kernel.php       the middleware pipeline
    │   ├── RouteHandler.php routing and action dispatch
    │   ├── Responder.php    action result → response
    │   ├── Emitter.php      response → SAPI
    │   ├── Method.php       request methods (enum)
    │   └── Status.php       status codes (enum)
    └── Route/               Router, Route, routes.xml converter, routes.xsd
bin/console                  console entry point
web/index.php                HTTP entry point (document root: web/)
tests/
├── Unit/                    unit and integration tests
├── Api/                     HTTP tests against a real server
└── Support/                 helpers, fixtures, the test configuration (Data/etc)
docs/                        this documentation
```

Namespaces follow PSR-4: `Core\` → `app/Core/`, `Application\` → `app/Application/`, `Tests\` → `tests/`.

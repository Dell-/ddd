# Configuration

## Environment variables

Settings that differ between environments come from the environment (12-factor), read in `di.xml` with
`<env>` arguments:

| Variable | Default | Used by |
|---|---|---|
| `APP_SECRET` | — **required** | `CsrfMiddleware` (token signature), at least 32 characters |
| `APP_DEBUG` | `false` | `ErrorHandlerMiddleware`, `Console\Application`, `display_errors` in `web/index.php` |
| `APP_CORS_ORIGINS` | empty | `CorsMiddleware`: allowed origins, comma/space separated |
| `APP_MAX_BODY_SIZE` | `10485760` | `BodySizeLimitMiddleware` |
| `APP_ETC_DIR` | `app/Application/etc` | `Bootstrap`: the configuration directory (constant `APP_ETC`) |

Add your own the same way:

```xml
<instance class="App\Mailer">
    <argument name="dsn"><env name="MAILER_DSN" default="null://null"/></argument>
</instance>
```

## Configuration files

The configuration directory (`APP_ETC`) contains:

- `di.xml` — services, the middleware pipeline, renderers ([Dependency injection](dependency-injection.md))
- `routes.xml` — routes and console commands ([Routing](../http/routing.md))

Both are validated against their XML schemas (`app/Core/Di/etc/di.xsd`, `app/Core/Route/etc/routes.xsd`)
when they are read; an invalid file fails with its name and line:

```
Invalid configuration file ".../routes.xml": Schema validation failed: line 4: Element 'route',
attribute 'method': [facet 'enumeration'] The value 'FOO' is not an element of the set {...}
```

Editors that understand `xsi:noNamespaceSchemaLocation` (PhpStorm, VS Code with an XML extension) offer
completion and validation while typing.

## Merging

All files matching the name pattern are merged, so environment- or module-specific files can override the
base configuration without copying it:

```
app/Application/etc/
├── di.xml          base (in git)
├── local.di.xml    overrides (e.g. not in git)
└── routes.xml
```

Order: fewer dots in the name first, then alphabetical — `di.xml`, `a.di.xml`, `local.di.xml`,
`b.local.di.xml`. Later files win.

| Element | Matched by | On match |
|---|---|---|
| `interface` | `name` | attributes replaced (`class`) |
| `instance` | `class` | attributes replaced, arguments merged by `name` |
| `argument` | `name` | the value is replaced as a whole |
| `callback` | — | always appended |
| `route`, `command` | `name` | attributes replaced (`method`, `path`, `action`, `trailing-slash`), `query` merged by `name` |
| `<routes>` attributes | — | replaced (`trailing-slash`) |

Example `local.di.xml` — a different logger name and a debug log file:

```xml
<di>
    <instance class="Monolog\Logger">
        <argument name="name"><string>my-app</string></argument>
    </instance>
</di>
```

Example `local.routes.xml` — replace the action of a route and add one:

```xml
<routes>
    <route name="home" method="GET" path="/" action="App\Action\NewHome"/>
    <route name="health" method="GET" path="/health" action="App\Action\Health"/>
</routes>
```

Every file is validated on its own before merging, so an overriding element must still have the attributes
the schema requires (a `route` needs `name`, `method`, `path` and `action`; an `instance` needs `class`).

## A different configuration directory

`APP_ETC_DIR=/etc/my-app php -S ...` uses another directory; the tests use this to run the application
with `tests/Support/Data/etc`. In code: `Bootstrap::createContainer('/path/to/etc')`.

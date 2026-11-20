# Конфігурація

## Змінні середовища

Налаштування, що відрізняються між середовищами, надходять зі змінних середовища (12-factor) і зчитуються в `di.xml`
через аргументи `<env>`:

| Змінна | Типове значення | Де використовується |
|---|---|---|
| `APP_SECRET` | — **обов’язкова** | `CsrfMiddleware` (підпис токена), щонайменше 32 символи |
| `APP_DEBUG` | `false` | `ErrorHandlerMiddleware`, `Console\Application`, `display_errors` у `web/index.php` |
| `APP_CORS_ORIGINS` | порожньо | `CorsMiddleware`: дозволені джерела (origins), розділені комами/пробілами |
| `APP_MAX_BODY_SIZE` | `10485760` | `BodySizeLimitMiddleware` |
| `APP_ETC_DIR` | `app/Application/etc` | `Bootstrap`: каталог конфігурації (константа `APP_ETC`) |

Додавайте власні так само:

```xml
<instance class="App\Mailer">
    <argument name="dsn"><env name="MAILER_DSN" default="null://null"/></argument>
</instance>
```

## Файли конфігурації

Каталог конфігурації (`APP_ETC`) містить:

- `di.xml` — сервіси, конвеєр middleware, рендерери ([Впровадження залежностей](dependency-injection.md))
- `routes.xml` — маршрути та консольні команди ([Маршрутизація](../http/routing.md))

Обидва файли під час читання валідуються за своїми XML-схемами (`app/Core/Di/etc/di.xsd`, `app/Core/Route/etc/routes.xsd`);
некоректний файл завершується помилкою з його назвою та рядком:

```
Invalid configuration file ".../routes.xml": Schema validation failed: line 4: Element 'route',
attribute 'method': [facet 'enumeration'] The value 'FOO' is not an element of the set {...}
```

Редактори, що розуміють `xsi:noNamespaceSchemaLocation` (PhpStorm, VS Code з розширенням для XML), пропонують
автодоповнення та валідацію під час набору.

## Злиття

Усі файли, що відповідають шаблону імені, зливаються, тож специфічні для середовища чи модуля файли можуть
перевизначати базову конфігурацію без її копіювання:

```
app/Application/etc/
├── di.xml          base (in git)
├── local.di.xml    overrides (e.g. not in git)
└── routes.xml
```

Порядок: спочатку імена з меншою кількістю крапок, потім за алфавітом — `di.xml`, `a.di.xml`, `local.di.xml`,
`b.local.di.xml`. Пізніші файли мають пріоритет.

| Елемент | Зіставляється за | У разі збігу |
|---|---|---|
| `interface` | `name` | атрибути замінюються (`class`) |
| `instance` | `class` | атрибути замінюються, аргументи зливаються за `name` |
| `argument` | `name` | значення замінюється повністю |
| `callback` | — | завжди додається |
| `route`, `command` | `name` | атрибути замінюються (`method`, `path`, `action`, `trailing-slash`), `query` зливається за `name` |
| атрибути `<routes>` | — | замінюються (`trailing-slash`) |

Приклад `local.di.xml` — інше ім’я логера та файл налагоджувального логу:

```xml
<di>
    <instance class="Monolog\Logger">
        <argument name="name"><string>my-app</string></argument>
    </instance>
</di>
```

Приклад `local.routes.xml` — замінити action маршруту та додати ще один маршрут:

```xml
<routes>
    <route name="home" method="GET" path="/" action="App\Action\NewHome"/>
    <route name="health" method="GET" path="/health" action="App\Action\Health"/>
</routes>
```

Кожен файл валідується окремо до злиття, тож елемент, що перевизначає, усе одно мусить мати атрибути,
яких вимагає схема (`route` потребує `name`, `method`, `path` і `action`; `instance` потребує `class`).

## Інший каталог конфігурації

`APP_ETC_DIR=/etc/my-app php -S ...` використовує інший каталог; тести користуються цим, щоб запускати застосунок
із `tests/Support/Data/etc`. У коді: `Bootstrap::createContainer('/path/to/etc')`.

# ddd

Lightweight PHP framework core: XML-configured DI container, XML routes, HTTP and console request handling.

Requires PHP 8.4+ (the Docker image uses PHP 8.5).

## Run with Docker

```sh
docker compose up -d --build          # HTTP server on http://localhost:8080
curl localhost:8080/                  # -> Application\Action\Http\Index

docker compose run --rm app php bin/console hello --name=World   # -> Hello, World!

docker compose down
```

`app/`, `web/` and `bin/` are mounted into the container, so code changes apply without a rebuild.
After changing `composer.json`, update the lock file and rebuild:

```sh
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app \
    composer:2 composer update --no-install
docker compose build
```

Logs are written to `app/Application/runtime/app-YYYY-MM-DD.log`.

## Structure

- `app/Bootstrap.php` — builds the DI container from `app/Application/etc/di.xml`
- `app/Application/etc/di.xml` — interface preferences and instance definitions
  (argument types: `string`, `int`, `float`, `bool`, `array`, `object`, `path`; `callback` calls a method after creation)
- `app/Application/etc/routes.xml` — `<route method="GET|POST|...|CLI" name="/path or command" action="Class"/>`
- `app/Application/Action/` — actions; each extends `Core\Request\Http\Action` or `Core\Request\Cli\Action`
  and returns its output from `execute()`
- `web/index.php` — HTTP entry point, `bin/console` — console entry point

# Installation

## Requirements

- PHP **8.4** or newer with `dom`, `libxml`, `fileinfo` and `mbstring` (all enabled in the official images)
- Composer 2

The repository ships a Docker setup, so neither PHP nor Composer has to be installed on the host.

## With Docker (recommended)

```sh
git clone git@github.com:Dell-/ddd.git && cd ddd
docker compose up -d --build        # HTTP server on http://localhost:8080
curl -i http://localhost:8080/
docker compose run --rm app php bin/console hello --name=World
```

`compose.yaml` builds the `dev` image target (with the development dependencies and the tests) and mounts
`app/`, `web/`, `bin/`, `tests/` and `codeception.yml`, so code changes apply without a rebuild.
`vendor/` lives in the image: rebuild it after changing `composer.json`:

```sh
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app \
    composer:2 composer update --no-install
docker compose build
```

The production image is the default target of the `Dockerfile`: no development dependencies, no tests.

```sh
docker build -t my-app .
docker run -p 8080:8080 -e APP_SECRET="$(openssl rand -hex 32)" my-app
```

The images run PHP's built-in server (`php -S`), which is fine for development and small deployments.
Behind nginx/Apache with PHP-FPM, point the document root to `web/` and route every request to `web/index.php`.

## Without Docker

```sh
composer install
APP_SECRET=$(openssl rand -hex 32) APP_DEBUG=1 php -S 127.0.0.1:8080 -t web web/index.php
```

## Environment

| Variable | Default | Meaning |
|---|---|---|
| `APP_SECRET` | — **required** | key for signing CSRF tokens, at least 32 characters |
| `APP_DEBUG` | `false` | exception details in 500 responses and console errors |
| `APP_CORS_ORIGINS` | empty | allowed CORS origins, separated by commas or spaces |
| `APP_MAX_BODY_SIZE` | `10485760` | request body limit in bytes |
| `APP_ETC_DIR` | `app/Application/etc` | configuration directory |

Development values are set in `compose.yaml`. See [Configuration](../architecture/configuration.md).

## Next steps

- [Quick start](quickstart.md)
- [Request lifecycle](../architecture/lifecycle.md)

# Встановлення

## Вимоги

- PHP **8.4** або новіший із розширеннями `dom`, `libxml`, `fileinfo` та `mbstring` (усі ввімкнені в офіційних образах)
- Composer 2

Репозиторій постачається з налаштуванням Docker, тож ані PHP, ані Composer не потрібно встановлювати на хості.

## З Docker (рекомендовано)

```sh
git clone git@github.com:Dell-/ddd.git && cd ddd
docker compose up -d --build        # HTTP server on http://localhost:8080
curl -i http://localhost:8080/
docker compose run --rm app php bin/console hello --name=World
```

`compose.yaml` збирає цільовий образ `dev` (із залежностями для розробки та тестами) і монтує
`app/`, `web/`, `bin/`, `tests/` та `codeception.yml`, тому зміни в коді застосовуються без перезбирання.
`vendor/` міститься в образі: перезберіть його після зміни `composer.json`:

```sh
docker run --rm -u "$(id -u):$(id -g)" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app \
    composer:2 composer update --no-install
docker compose build
```

Продакшн-образ — це цільовий образ `Dockerfile` за замовчуванням: без залежностей для розробки, без тестів.

```sh
docker build -t my-app .
docker run -p 8080:8080 -e APP_SECRET="$(openssl rand -hex 32)" my-app
```

Образи запускають вбудований сервер PHP (`php -S`), якого достатньо для розробки та невеликих розгортань.
За nginx/Apache з PHP-FPM вкажіть як кореневий каталог документів `web/` і спрямовуйте кожен запит на `web/index.php`.

## Без Docker

```sh
composer install
APP_SECRET=$(openssl rand -hex 32) APP_DEBUG=1 php -S 127.0.0.1:8080 -t web web/index.php
```

## Середовище

| Змінна | Типове значення | Призначення |
|---|---|---|
| `APP_SECRET` | — **обов’язкова** | ключ для підпису токенів CSRF, щонайменше 32 символи |
| `APP_DEBUG` | `false` | подробиці винятків у відповідях 500 і в помилках консолі |
| `APP_CORS_ORIGINS` | порожньо | дозволені джерела (origins) CORS, розділені комами або пробілами |
| `APP_MAX_BODY_SIZE` | `10485760` | ліміт розміру тіла запиту в байтах |
| `APP_ETC_DIR` | `app/Application/etc` | каталог конфігурації |

Значення для розробки задано в `compose.yaml`. Див. [Конфігурація](../architecture/configuration.md).

## Наступні кроки

- [Швидкий старт](quickstart.md)
- [Життєвий цикл запиту](../architecture/lifecycle.md)

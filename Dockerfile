FROM php:8.5-cli AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "web", "web/index.php"]

# Development: with dev dependencies (Codeception) and tests
FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize \
    && chmod -R a+rwX app/Application/runtime

# Production (default target): runtime dependencies only, no tests
FROM base AS prod

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .
RUN rm -rf tests codeception.yml \
    && composer dump-autoload --no-dev --optimize \
    && chmod -R a+rwX app/Application/runtime

# Документація

> 🇬🇧 [English version](../en/README.md)

Невеликий PHP 8.4+ фреймворк, побудований на PSR-7, PSR-15 і PSR-17 та на HTTP RFC: контейнер впровадження
залежностей із XML-конфігурацією, маршрутизатор із явними правилами URL, узгодження вмісту (content negotiation),
problem details, завантаження та віддача файлів, захист CORS і CSRF, а також консоль.

## Початок роботи

- [Встановлення](getting-started/installation.md) — Docker, вимоги, перший запуск
- [Швидкий старт](getting-started/quickstart.md) — JSON endpoint з тестом за п’ять хвилин
- [Структура каталогів](getting-started/directory-structure.md)

## Архітектура

- [Життєвий цикл запиту](architecture/lifecycle.md) — від `web/index.php` до відправленої відповіді
- [Впровадження залежностей](architecture/dependency-injection.md) — `di.xml`, автопідстановка (autowiring), спільні сервіси, аргументи
- [Конфігурація](architecture/configuration.md) — змінні середовища, файли конфігурації, злиття, валідація

## HTTP

- [Маршрутизація](http/routing.md) — маршрути, параметри, параметри запиту, політика кінцевого слеша, генерація URL
- [Actions](http/actions.md) — обробники, результати, залежності
- [Запити](http/requests.md) — читання query, тіла, заголовків, cookie, параметрів маршруту
- [Відповіді](http/responses.md) — JSON, JSON Lines, HTML, текст, XML, редиректи, порожні відповіді, cookie
- [Представлення та узгодження вмісту](http/content-negotiation.md) — один action, багато форматів; рендерери та шаблони
- [Помилки](http/errors.md) — HTTP-винятки та problem details (RFC 9457)
- [Файли](http/files.md) — завантаження на сервер (multipart, сире тіло, data URI) і віддача (діапазони, кешування)
- [Middleware](http/middleware.md) — конвеєр PSR-15 і написання middleware
- [Безпека](http/security.md) — CORS, CSRF, заголовки безпеки, обмеження

## Додатково

- [Консоль](console.md) — команди
- [Тестування](testing.md) — модульні та HTTP-набори тестів Codeception
- Довідник: [стандарти](reference/standards.md) · [статуси та винятки](reference/status-codes.md) ·
  [схеми конфігурації](reference/configuration-schemas.md)

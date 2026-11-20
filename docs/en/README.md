# Documentation

> 🇺🇦 [Українська версія](../uk/README.md)

A small PHP 8.4+ framework built on PSR-7, PSR-15 and PSR-17 and on the HTTP RFCs: an XML-configured
dependency injection container, a router with explicit URL rules, content negotiation, problem details,
uploads and downloads, CORS and CSRF protection, and a console.

## Getting started

- [Installation](getting-started/installation.md) — Docker, requirements, first run
- [Quick start](getting-started/quickstart.md) — a JSON endpoint with a test in five minutes
- [Directory structure](getting-started/directory-structure.md)

## Architecture

- [Request lifecycle](architecture/lifecycle.md) — from `web/index.php` to the emitted response
- [Dependency injection](architecture/dependency-injection.md) — `di.xml`, autowiring, shared services, arguments
- [Configuration](architecture/configuration.md) — environment variables, configuration files, merging, validation

## HTTP

- [Routing](http/routing.md) — routes, parameters, query parameters, trailing slash policy, URL generation
- [Actions](http/actions.md) — handlers, results, dependencies
- [Requests](http/requests.md) — reading the query, the body, headers, cookies, route parameters
- [Responses](http/responses.md) — JSON, JSON Lines, HTML, text, XML, redirects, empty responses, cookies
- [Views and content negotiation](http/content-negotiation.md) — one action, many formats; renderers and templates
- [Errors](http/errors.md) — HTTP exceptions and problem details (RFC 9457)
- [Files](http/files.md) — uploads (multipart, raw body, data URIs) and downloads (ranges, caching)
- [Middleware](http/middleware.md) — the PSR-15 pipeline and writing middleware
- [Security](http/security.md) — CORS, CSRF, security headers, limits

## More

- [Console](console.md) — commands
- [Testing](testing.md) — Codeception unit and HTTP suites
- Reference: [standards](reference/standards.md) · [status codes and exceptions](reference/status-codes.md) ·
  [configuration schemas](reference/configuration-schemas.md)

<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

use Core\Http\View\TemplateEngineInterface;

/**
 * Template engine for tests: "item" renders "<h1>{name}</h1>", other templates do not exist.
 */
final class TemplateEngine implements TemplateEngineInterface
{
    #[\Override]
    public function exists(string $template): bool
    {
        return $template === 'item';
    }

    #[\Override]
    public function render(string $template, array $context = []): string
    {
        return '<h1>' . htmlspecialchars((string) ($context['name'] ?? '')) . '</h1>';
    }
}

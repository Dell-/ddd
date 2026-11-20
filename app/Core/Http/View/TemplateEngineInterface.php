<?php

declare(strict_types=1);

namespace Core\Http\View;

/**
 * Template engine for HtmlRenderer (an adapter for Twig, Latte, plain PHP templates, ...).
 * Not bundled yet: configure an implementation as HtmlRenderer's "engine" argument in di.xml.
 */
interface TemplateEngineInterface
{
    public function exists(string $template): bool;

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string;
}

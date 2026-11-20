<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * text/html: a View with a template is rendered by the template engine (with the view context and
 * the request as "request"); string/Stringable data is sent as it is (already HTML).
 * Without an engine only the latter works, so data views are then served in other formats.
 */
final class HtmlRenderer implements RendererInterface
{
    public function __construct(private readonly ?TemplateEngineInterface $engine = null)
    {
    }

    #[\Override]
    public function getMediaTypes(): array
    {
        return ['text/html', 'application/xhtml+xml'];
    }

    #[\Override]
    public function supports(View $view): bool
    {
        if ($view->template !== null) {
            return $this->engine?->exists($view->template) ?? false;
        }

        return is_string($view->data) || $view->data instanceof \Stringable;
    }

    #[\Override]
    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface
    {
        $html = $view->template !== null && $this->engine !== null
            ? $this->engine->render($view->template, $view->context() + ['request' => $request])
            : (string) $view->data;

        return new HtmlResponse($html, $view->status, $view->headers);
    }
}

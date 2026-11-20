<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * text/plain for scalar and Stringable data.
 */
final class TextRenderer implements RendererInterface
{
    #[\Override]
    public function getMediaTypes(): array
    {
        return ['text/plain'];
    }

    #[\Override]
    public function supports(View $view): bool
    {
        return is_scalar($view->data) || $view->data instanceof \Stringable || $view->data === null;
    }

    #[\Override]
    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface
    {
        $text = is_bool($view->data) ? ($view->data ? 'true' : 'false') : (string) $view->data;

        return new TextResponse($text, $view->status, $view->headers);
    }
}

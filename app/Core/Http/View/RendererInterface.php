<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Renders a View into one or more media types. The extension point for new formats: implement it
 * and add the renderer to ViewRenderer in di.xml (the order is the server's preference).
 */
interface RendererInterface
{
    /**
     * Media types this renderer produces, e.g. ["application/json"].
     *
     * @return list<string>
     */
    public function getMediaTypes(): array;

    /**
     * Whether the renderer can render this view (e.g. HTML needs a template or a string).
     */
    public function supports(View $view): bool;

    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface;
}

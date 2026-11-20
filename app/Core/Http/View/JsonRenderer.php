<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;

final class JsonRenderer implements RendererInterface
{
    public function __construct(private readonly int $flags = JsonResponse::DEFAULT_FLAGS)
    {
    }

    #[\Override]
    public function getMediaTypes(): array
    {
        return ['application/json'];
    }

    #[\Override]
    public function supports(View $view): bool
    {
        return true;
    }

    #[\Override]
    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface
    {
        return new JsonResponse($view->data, $view->status, $view->headers, $this->flags, $mediaType);
    }
}

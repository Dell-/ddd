<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\XmlResponse;
use Psr\Http\Message\ResponseInterface;

final class XmlRenderer implements RendererInterface
{
    public function __construct(private readonly XmlEncoder $encoder = new XmlEncoder())
    {
    }

    #[\Override]
    public function getMediaTypes(): array
    {
        return ['application/xml', 'text/xml'];
    }

    #[\Override]
    public function supports(View $view): bool
    {
        return true;
    }

    #[\Override]
    public function render(View $view, string $mediaType, ServerRequestInterface $request): ResponseInterface
    {
        $xml = $view->data instanceof \DOMDocument || $view->data instanceof \SimpleXMLElement
            ? $view->data
            : $this->encoder->encode($view->data);

        return new XmlResponse($xml, $view->status, $view->headers, $mediaType);
    }
}

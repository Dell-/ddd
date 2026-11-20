<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Exception\NotAcceptableException;
use Core\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Proactive content negotiation (RFC 9110 §12.1) over the registered renderers.
 *
 * Candidates are the media types of the renderers that support the view, in registration order
 * (the server preference). The best by the Accept header wins; if none is acceptable, 406.
 * Responses carry "Vary: Accept" (RFC 9110 §12.5.5).
 */
final class ViewRenderer
{
    /**
     * @param list<RendererInterface> $renderers
     */
    public function __construct(private readonly array $renderers = [new JsonRenderer(), new XmlRenderer(), new HtmlRenderer(), new TextRenderer()])
    {
    }

    /**
     * @throws NotAcceptableException
     */
    public function render(View $view, ServerRequestInterface $request): ResponseInterface
    {
        $candidates = [];
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($view)) {
                foreach ($renderer->getMediaTypes() as $mediaType) {
                    $candidates[$mediaType] ??= $renderer;
                }
            }
        }

        $mediaType = $request->getAccept()->negotiate(array_keys($candidates));
        if ($mediaType === null) {
            throw new NotAcceptableException(array_keys($candidates));
        }

        $response = $candidates[$mediaType]->render($view, $mediaType, $request);
        $vary = $response->getHeaderLine('Vary');

        return $vary === '' ? $response->withHeader('Vary', 'Accept') : $response->withHeader('Vary', $vary . ', Accept');
    }

    /**
     * @return list<string>
     */
    public function getMediaTypes(View $view): array
    {
        $types = [];
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($view)) {
                array_push($types, ...$renderer->getMediaTypes());
            }
        }

        return array_values(array_unique($types));
    }
}

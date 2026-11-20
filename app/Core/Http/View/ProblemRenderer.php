<?php

declare(strict_types=1);

namespace Core\Http\View;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\HtmlResponse;
use Core\Http\Response\JsonResponse;
use Core\Http\Response\TextResponse;
use Core\Http\Response\XmlResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Renders problem details (RFC 9457) negotiated by Accept:
 * application/problem+json (also for "application/json"), application/problem+xml (Appendix B,
 * also for "application/xml"), text/html and text/plain. Without a match: problem+json.
 */
final class ProblemRenderer
{
    public const string NAMESPACE_XML = 'urn:ietf:rfc:7807';

    private const array CANDIDATES = [
        'application/problem+json' => 'json',
        'application/json' => 'json',
        'application/problem+xml' => 'xml',
        'application/xml' => 'xml',
        'text/xml' => 'xml',
        'text/html' => 'html',
        'text/plain' => 'text',
    ];

    /**
     * @param array<string, string|list<string>> $headers
     */
    public function render(ProblemDetails $problem, ServerRequestInterface $request, array $headers = []): ResponseInterface
    {
        $headers += ['Vary' => 'Accept'];
        $format = self::CANDIDATES[$request->getAccept()->negotiate(array_keys(self::CANDIDATES)) ?? 'application/problem+json'];

        return match ($format) {
            'json' => new JsonResponse($problem, $problem->status, $headers, mediaType: 'application/problem+json'),
            'xml' => new XmlResponse($this->xml($problem), $problem->status, $headers, 'application/problem+xml'),
            'html' => new HtmlResponse($this->html($problem), $problem->status, $headers),
            'text' => new TextResponse($problem->detail ?? $problem->title, $problem->status, $headers),
        };
    }

    private function xml(ProblemDetails $problem): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $root = $document->createElementNS(self::NAMESPACE_XML, 'problem');
        $document->appendChild($root);
        $this->appendXml($document, $root, $problem->jsonSerialize());

        return (string) $document->saveXML();
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function appendXml(\DOMDocument $document, \DOMElement $parent, array $data): void
    {
        foreach ($data as $key => $value) {
            $name = is_int($key) ? 'i' : (preg_match('/^[A-Za-z_][\w.-]*$/', $key) ? $key : '_' . preg_replace('/\W/', '_', $key));
            $element = $document->createElementNS(self::NAMESPACE_XML, $name);
            $parent->appendChild($element);
            if (is_array($value)) {
                $this->appendXml($document, $element, $value);
            } elseif ($value !== null) {
                $element->appendChild($document->createTextNode(is_bool($value) ? ($value ? 'true' : 'false') : (string) (is_scalar($value) || $value instanceof \Stringable ? $value : json_encode($value))));
            }
        }
    }

    private function html(ProblemDetails $problem): string
    {
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
        $detail = $problem->detail !== null ? '<p>' . $escape($problem->detail) . '</p>' : '';

        return sprintf(
            "<!DOCTYPE html>\n<html lang=\"en\"><head><meta charset=\"utf-8\"><title>%d %s</title></head>"
            . "<body><h1>%d %s</h1>%s</body></html>\n",
            $problem->status,
            $escape($problem->title),
            $problem->status,
            $escape($problem->title),
            $detail
        );
    }
}

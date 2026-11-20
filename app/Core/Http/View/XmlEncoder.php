<?php

declare(strict_types=1);

namespace Core\Http\View;

/**
 * Encodes data as XML: keys become elements, lists repeat the item element, null is xsi:nil.
 *
 *     ['id' => 1, 'tags' => ['a', 'b']]  =>  <response><id>1</id><tags><item>a</item><item>b</item></tags></response>
 */
final readonly class XmlEncoder
{
    public const string XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct(
        private string $rootElement = 'response',
        private string $itemElement = 'item',
    ) {
    }

    public function encode(mixed $data, ?string $rootElement = null): string
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $root = $document->createElement($this->elementName($rootElement ?? $this->rootElement));
        $document->appendChild($root);
        $this->append($document, $root, $data);

        return (string) $document->saveXML();
    }

    private function append(\DOMDocument $document, \DOMElement $element, mixed $data): void
    {
        if ($data instanceof \JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof \BackedEnum) {
            $data = $data->value;
        } elseif ($data instanceof \DateTimeInterface) {
            $data = $data->format(DATE_RFC3339);
        } elseif (is_object($data) && !$data instanceof \Stringable) {
            $data = get_object_vars($data);
        }

        match (true) {
            $data === null => $element->setAttributeNS(self::XSI, 'xsi:nil', 'true'),
            is_bool($data) => $element->appendChild($document->createTextNode($data ? 'true' : 'false')),
            is_scalar($data), $data instanceof \Stringable => $element->appendChild($document->createTextNode((string) $data)),
            is_array($data) => $this->appendArray($document, $element, $data),
            default => throw new \InvalidArgumentException(sprintf('Cannot encode %s as XML.', get_debug_type($data))),
        };
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function appendArray(\DOMDocument $document, \DOMElement $element, array $data): void
    {
        foreach ($data as $key => $value) {
            $child = $document->createElement(is_int($key) ? $this->itemElement : $this->elementName($key));
            $element->appendChild($child);
            $this->append($document, $child, $value);
        }
    }

    /**
     * A valid XML name: invalid characters become "_", a name must not start with a digit or "xml".
     */
    private function elementName(string $name): string
    {
        $name = (string) preg_replace('/[^\p{L}\p{N}_.\-]/u', '_', $name);
        if ($name === '' || preg_match('/^[\p{N}.\-]|^xml/iu', $name)) {
            $name = '_' . $name;
        }

        return $name;
    }
}

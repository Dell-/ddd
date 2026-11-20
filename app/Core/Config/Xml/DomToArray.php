<?php

declare(strict_types=1);

namespace Core\Config\Xml;

/**
 * Converts a DOM document into a nested array:
 *   element name => list of [ '@attributes' => [...], '@value' => '...', child name => [...] ]
 * The returned array holds the children of the root element.
 */
class DomToArray
{
    public const ATTRIBUTES = '@attributes';
    public const VALUE = '@value';

    /**
     * @return array<string, mixed>
     */
    public function convert(\DOMDocument $document): array
    {
        if ($document->documentElement === null) {
            return [];
        }

        $data = [];
        $this->toArray($document->documentElement, $data);

        return reset($data)[0] ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function toArray(\DOMNode $node, array &$data): void
    {
        switch ($node->nodeType) {
            case XML_TEXT_NODE:
            case XML_CDATA_SECTION_NODE:
                if (trim($node->textContent) !== '') {
                    $data[self::VALUE] = ($data[self::VALUE] ?? '') . $node->textContent;
                }
                break;
            case XML_ELEMENT_NODE:
                $nodeData = [];
                foreach ($node->attributes ?? [] as $attributeName => $attribute) {
                    $nodeData[self::ATTRIBUTES][$attributeName] = $attribute->value;
                }
                foreach ($node->childNodes as $childNode) {
                    $this->toArray($childNode, $nodeData);
                }
                $data[$node->localName][] = $nodeData;
                break;
        }
    }
}

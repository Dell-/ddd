<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Merges several XML documents into one, the later documents override the earlier ones.
 *
 * For each element of a merged document:
 * - if the base document has a matching sibling (see MergeRules), the attributes are overridden
 *   and the content is merged: text replaces text, child elements are merged recursively,
 *   or the whole content is replaced for "replace content" elements;
 *   an element without content changes only the attributes;
 * - otherwise the element is appended.
 */
class Merger
{
    protected ?\DOMDocument $domDocument = null;

    public function __construct(
        protected readonly MergeRules $rules = new MergeRules(),
        protected readonly Factory $domFactory = new Factory(),
    ) {
    }

    /**
     * @throws \InvalidArgumentException on invalid XML, failed schema validation or different root elements
     */
    public function merge(string $xmlContent, ?string $schemaFilePath = null): void
    {
        $document = $this->domFactory->create($xmlContent);
        if ($schemaFilePath !== null) {
            $this->validate($document, $schemaFilePath);
        }

        if ($this->domDocument === null) {
            $this->domDocument = $document;

            return;
        }

        $source = $document->documentElement;
        $target = $this->domDocument->documentElement;
        if (!$this->isSameElement($source, $target)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot merge <%s> into <%s>: root elements differ.',
                $source->nodeName,
                $target->nodeName
            ));
        }

        $this->mergeElement($source, $target);
    }

    public function getContent(): string
    {
        return (string) $this->getDocument()->saveXML();
    }

    public function getDOMDocument(): \DOMDocument
    {
        return clone $this->getDocument();
    }

    protected function getDocument(): \DOMDocument
    {
        return $this->domDocument ?? throw new \LogicException('Nothing has been merged yet.');
    }

    protected function mergeElement(\DOMElement $source, \DOMElement $target): void
    {
        $this->mergeAttributes($source, $target);

        $children = $this->childElements($source);
        if ($children === []) {
            if (trim($source->textContent) !== '') {
                $target->textContent = $source->textContent;
            }

            return;
        }

        if ($this->rules->isReplaceContent($target)) {
            while ($target->firstChild !== null) {
                $target->removeChild($target->firstChild);
            }
            foreach ($source->childNodes as $node) {
                $target->appendChild($this->getDocument()->importNode($node, true));
            }

            return;
        }

        foreach ($children as $child) {
            $match = $this->findMatch($child, $target);
            if ($match !== null) {
                $this->mergeElement($child, $match);
            } else {
                $target->appendChild($this->getDocument()->importNode($child, true));
            }
        }
    }

    protected function mergeAttributes(\DOMElement $source, \DOMElement $target): void
    {
        foreach ($source->attributes as $attribute) {
            if ($attribute->namespaceURI !== null) {
                $target->setAttributeNS($attribute->namespaceURI, $attribute->nodeName, $attribute->value);
            } else {
                $target->setAttribute($attribute->nodeName, $attribute->value);
            }
        }
    }

    /**
     * Finds the child of $parent the element must be merged into.
     */
    protected function findMatch(\DOMElement $element, \DOMElement $parent): ?\DOMElement
    {
        $keys = $this->rules->getKeyAttributes($element);
        if ($keys === []) {
            return null;
        }
        foreach ($keys as $key) {
            if (!$element->hasAttribute($key)) {
                return null;
            }
        }

        foreach ($this->childElements($parent) as $candidate) {
            if (!$this->isSameElement($element, $candidate)) {
                continue;
            }
            foreach ($keys as $key) {
                if ($candidate->getAttribute($key) !== $element->getAttribute($key)) {
                    continue 2;
                }
            }

            return $candidate;
        }

        return null;
    }

    /**
     * @return list<\DOMElement>
     */
    protected function childElements(\DOMElement $element): array
    {
        $result = [];
        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMElement) {
                $result[] = $node;
            }
        }

        return $result;
    }

    protected function isSameElement(\DOMElement $a, \DOMElement $b): bool
    {
        return $a->localName === $b->localName && $a->namespaceURI === $b->namespaceURI;
    }

    protected function validate(\DOMDocument $document, string $schemaFilePath): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$document->schemaValidate($schemaFilePath)) {
                $messages = array_map(
                    static fn (\LibXMLError $error): string => sprintf('line %d: %s', $error->line, trim($error->message)),
                    libxml_get_errors()
                );
                throw new \InvalidArgumentException(
                    'Schema validation failed: ' . ($messages === [] ? 'unknown error' : implode('; ', $messages))
                );
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}

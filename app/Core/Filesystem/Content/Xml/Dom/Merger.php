<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Merges several XML documents into one.
 * Elements with the same id attribute (see $idAttributes) are merged, the rest are appended.
 */
class Merger
{
    /**
     * @var string[]
     */
    protected array $idAttributes = ['name'];

    protected ?\DOMDocument $domDocument = null;

    protected ?\DOMXPath $xPath = null;

    public function __construct(
        protected readonly Factory $domFactory = new Factory(),
        protected readonly XPathFactory $xPathFactory = new XPathFactory(),
    ) {
    }

    public function merge(string $xmlContent, ?string $schemaFilePath = null): void
    {
        $domDocument = $this->domFactory->create($xmlContent);
        if ($schemaFilePath !== null && !$domDocument->schemaValidate($schemaFilePath)) {
            throw new \InvalidArgumentException('Invalid XML file, schema validation fails.');
        }

        if ($this->domDocument === null) {
            $this->domDocument = $domDocument;
            $this->xPath = $this->xPathFactory->create($this->domDocument);

            return;
        }

        $root = $this->domDocument->documentElement;
        if ($domDocument->documentElement->hasChildNodes()) {
            $this->nestedMerge($domDocument->documentElement->childNodes, $root);
        }
        $this->mergeNodeAttributes($domDocument->documentElement, $root);
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

    protected function nestedMerge(\DOMNodeList $nodeList, \DOMNode $rootNode): void
    {
        foreach (iterator_to_array($nodeList) as $node) {
            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $queryResult = $this->xPath->query($this->createXPath($node), $rootNode);
            if ($queryResult !== false && $queryResult->length > 0 && $this->hasMergeAttribute($node)) {
                $this->eachItems($queryResult, $node);
            } else {
                $this->appendNode($node, $rootNode);
            }
        }
    }

    protected function eachItems(\DOMNodeList $rootNodeList, \DOMNode $node): void
    {
        foreach ($rootNodeList as $itemNode) {
            if ($this->isTextNode($node) && $this->isTextNode($itemNode)) {
                $itemNode->textContent = $node->textContent;
            } elseif ($node->hasChildNodes()) {
                $this->nestedMerge($node->childNodes, $itemNode);
            }
        }
    }

    protected function appendNode(\DOMNode $node, \DOMNode $rootNode): void
    {
        $rootNode->appendChild($this->getDocument()->importNode($node, true));
    }

    protected function mergeNodeAttributes(\DOMElement $element, \DOMElement $rootElement): void
    {
        foreach ($element->attributes as $name => $attribute) {
            $rootElement->setAttribute($name, $attribute->value);
        }
    }

    /**
     * XPath of the node relative to the document root, e.g. "/route[@name="/"]".
     */
    protected function createXPath(\DOMNode $node): string
    {
        if ($node instanceof \DOMDocument || $node->parentNode === null) {
            return '';
        }

        $xPathAttributes = [];
        foreach ($node->attributes ?? [] as $name => $attribute) {
            if (in_array($name, $this->idAttributes, true)) {
                $xPathAttributes[] = sprintf('@%s="%s"', $name, $attribute->value);
            }
        }

        $nodePath = (string) $node->getNodePath();
        $position = strrpos($nodePath, '[');
        if ($xPathAttributes !== []) {
            $xPath = $node->nodeName . '[' . implode(' and ', $xPathAttributes) . ']';
        } elseif ($position !== false && !str_contains(substr($nodePath, $position), '/')) {
            $xPath = $node->nodeName . substr($nodePath, $position);
        } else {
            $xPath = $node->nodeName;
        }

        return $this->createXPath($node->parentNode) . '/' . $xPath;
    }

    protected function hasMergeAttribute(\DOMNode $node): bool
    {
        foreach ($node->attributes ?? [] as $name => $attribute) {
            if (in_array($name, $this->idAttributes, true)) {
                return true;
            }
        }

        return false;
    }

    protected function isTextNode(\DOMNode $node): bool
    {
        return $node->childNodes->length === 1 && $node->firstChild instanceof \DOMText;
    }
}

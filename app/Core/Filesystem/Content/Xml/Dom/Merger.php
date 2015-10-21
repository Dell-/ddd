<?php
namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Class Merger
 */
class Merger
{
    /**
     * ID attributes for the unique element [default = name]
     *
     * @var array
     */
    protected $idAttributes = ['name'];

    /**
     * @var \DOMDocument
     */
    protected $domDocument;

    /**
     * @var Factory
     */
    protected $domFactory;

    /**
     * @var XPathFactory
     */
    protected $xPathFactory;

    /**
     * @var \DOMXPath
     */
    protected $xPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->domFactory = new Factory();
        $this ->xPathFactory = new XPathFactory();
    }

    /**
     * Merge XML content into one document
     *
     * @param string $xmlContent
     * @param string $schemaFilePath [optional]
     * @throws \Exception
     */
    public function merge($xmlContent, $schemaFilePath = null)
    {
        $domDocument = $this->domFactory->create($xmlContent);
        if ($schemaFilePath !== null && !$domDocument->schemaValidate($schemaFilePath)) {
            throw new \Exception('Invalid XML file, schema validation fails.');
        }
        if (!isset($this->domDocument)) {
            $this->domDocument = $domDocument;
            $this->xPath = $this->xPathFactory->create($this->domDocument);
        } else {
            if ($domDocument->documentElement->hasChildNodes()) {
                $this->nestedMerge($domDocument->documentElement->childNodes, $this->domDocument->documentElement);
            }
            $this->mergeNodeAttributes($domDocument->documentElement, $this->domDocument->documentElement);
        }
    }

    /**
     * Get XML content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->domDocument->saveXML();
    }

    /**
     * Get DOM document
     *
     * @return \DOMDocument
     */
    public function getDOMDocument()
    {
        return clone $this->domDocument;
    }

    /**
     * Nested merge document dom
     *
     * @param \DOMNodeList $nodeList
     * @param \DOMNode $rootNode
     */
    protected function nestedMerge(\DOMNodeList $nodeList, \DOMNode $rootNode = null)
    {
        for ($iLength = $nodeList->length, $iIndex = 0; $iIndex < $iLength; ++$iIndex) {
            $node = $nodeList->item($iIndex);
            switch ($node->nodeType) {
                case XML_TEXT_NODE:
                case XML_COMMENT_NODE:
                case XML_CDATA_SECTION_NODE:
                    break;
                case XML_ELEMENT_NODE:
                    $xPath = $this->createXPath($node);
                    $queryResult = $this->xPath->query($xPath, $rootNode);
                    if ($queryResult->length > 0 && $this->hasMergeAttribute($node)) {
                        $this->eachItems($queryResult, $node);
                    } else {
                        $this->appendNode($node, $rootNode);
                    }
                    break;
            }
        }
    }

    /**
     * Merge each items list
     *
     * @param \DOMNodeList $rootNodeList
     * @param \DOMNode $node
     */
    protected function eachItems(\DOMNodeList $rootNodeList, \DOMNode $node)
    {
        for ($iLength = $rootNodeList->length, $iIndex = 0; $iIndex < $iLength; ++$iIndex) {
            $itemNode = $rootNodeList->item($iIndex);
            if ($this->isTextNode($node) && $this->isTextNode($itemNode)) {
                $itemNode->textContent = $node->textContent;
            } else if ($node->hasChildNodes()) {
                $this->nestedMerge($node->childNodes, $itemNode);
            }
        }
    }

    /**
     * Append node
     *
     * @param \DOMNode $node
     * @param \DOMNode $rootNode
     */
    protected function appendNode(\DOMNode $node, \DOMNode $rootNode)
    {
        $node = $this->domDocument->importNode($node, true);
        $rootNode->appendChild($node);
    }

    /**
     * Merge node attributes
     *
     * @param \DOMElement $element
     * @param \DOMElement $rootElement
     */
    protected function mergeNodeAttributes(\DOMElement $element, \DOMElement $rootElement)
    {
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $name => $attribute) {
                $rootElement->setAttribute($name, $attribute->value);
            }
        }
    }

    /**
     * Create XPath string
     *
     * @param \DOMNode $node
     * @return string
     */
    protected function createXPath(\DOMNode $node)
    {
        if ($node->ownerDocument === null || $node->parentNode === null) {
            return '';
        }

        $xPathAttributes = [];
        $parentXPath = $this->createXPath($node->parentNode);
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $name => $attribute) {
                if (in_array($name, $this->idAttributes)) {
                    $xPathAttributes[] = sprintf('@%s="%s"', $name, $attribute->value);
                }
            }
        }

        if (!empty($xPathAttributes)) {
            $xPath = $node->nodeName . '[' . implode(' and ', $xPathAttributes) . ']';
        } else if (strrpos($node->getNodePath(), '[') !==  false) {
            $xPath = $node->nodeName . substr($node->getNodePath(), strrpos($node->getNodePath(), '['));
        } else {
            $xPath = $node->nodeName;
        }

        return $parentXPath . '/' . $xPath;
    }

    /**
     * Has id attribute in node
     *
     * @param \DOMNode $node
     * @return bool
     */
    protected function hasMergeAttribute(\DOMNode $node)
    {
        if ($node->hasAttributes()) {
            foreach ($node->attributes as $name => $attribute) {
                if (in_array($name, $this->idAttributes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Whether the node DOMText
     *
     * @param \DOMNode $node
     * @return bool
     */
    protected function isTextNode(\DOMNode $node)
    {
        return $node->hasChildNodes()
            && $node->childNodes->length === 1
            && $node->childNodes->item(0) instanceof \DOMText;
    }
}

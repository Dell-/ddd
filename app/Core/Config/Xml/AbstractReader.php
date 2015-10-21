<?php
namespace Core\Config\Xml;

use Core\Filesystem\Reader;
use Core\Config\ReaderInterface;
use Core\Filesystem\Content\Xml\Dom\Merger;

/**
 * Class AbstractReader
 */
abstract class AbstractReader implements ReaderInterface
{
    const ROOT_NAME = 'config';

    /**
     * @var Reader
     */
    protected $filesystemReader;

    /**
     * @var Merger
     */
    protected $merger;

    /**
     * @var string
     */
    protected $directory;

    /**
     * Constructor
     *
     * @param string $directory
     * @param Reader $filesystemReader
     * @param Merger $merger
     */
    public function __construct($directory, Reader $filesystemReader, Merger $merger)
    {
        $this->filesystemReader = $filesystemReader;
        $this->merger = $merger;
        $this->directory = $directory;
    }

    /**
     * @return array
     */
    public function read()
    {
        $config = [];
        foreach ($this->getFiles($this->directory) as $content) {
            $this->merger->merge($content);
        }
        $this->toArray($this->merger->getDOMDocument()->documentElement, $config);

        return reset($config[static::ROOT_NAME]);
    }

    /**
     * @param \DOMNode $node
     * @param array $data
     */
    protected function toArray(\DOMNode $node, array &$data)
    {
        switch ($node->nodeType) {
            case XML_TEXT_NODE:
            case XML_COMMENT_NODE:
            case XML_CDATA_SECTION_NODE:
                $textContent = trim($node->textContent);
                if (!empty($textContent)) {
                    if (!isset($data[static::VALUE])) {
                        $data[static::VALUE] = '';
                    }
                    $data[static::VALUE] .= $node->textContent;
                }
                break;
            case XML_ELEMENT_NODE:
                $nodeData = [];
                $name = $node->localName;
                if (!isset($data[$name])) {
                    $data[$name] = [];
                }
                if ($node->hasAttributes()) {
                    foreach ($node->attributes as $attributeName => $attribute) {
                        $nodeData[static::ATTRIBUTES][$attributeName] = $attribute->value;
                    }
                }
                if ($node->hasChildNodes()) {
                    /** @var \DOMNode $childNode */
                    foreach ($node->childNodes as $childNode) {
                        $this->toArray($childNode, $nodeData);
                    }
                }

                $data[$name][] = $nodeData;
                break;
        }
    }

    /**
     * @param string $directory
     * @return array
     */
    abstract protected function getFiles($directory);
}

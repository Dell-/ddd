<?php
namespace Core\Di\Config\Xml;

use Core\Di\Config\Argument\Service;
use Core\Di\InstanceClass;
use Core\Config\ConverterInterface;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    const INTERFACE_ITEM = 'interface';

    const INSTANCE_ITEM = 'instance';

    const ARGUMENT_ITEM = 'argument';

    const CALLBACK_ITEM = 'callback';

    const ROOT_ITEM = 'config';

    const ATTRIBUTES = '@attributes';

    const VALUE = '@value';

    /**
     * @var Service
     */
    private $service;

    /**
     * @var InstanceClass[]
     */
    private $convertedData = [];

    /**
     * Constructor
     *
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @inheritdoc
     */
    public function convert(\DOMDocument $document)
    {
        $data = [];
        $this->toArray($document->documentElement, $data);
        $data = reset($data);
        $data = reset($data);

        $this->createInstance($data);
        $this->createPreference($data);

        return $this->convertedData;
    }

    /**
     * @param \DOMNode $node
     * @param array $data
     */
    private function toArray(\DOMNode $node, array &$data)
    {
        switch ($node->nodeType) {
            case XML_TEXT_NODE:
            case XML_COMMENT_NODE:
            case XML_CDATA_SECTION_NODE:
                $textContent = trim($node->textContent);
                if (!empty($textContent)) {
                    if (!isset($data[self::VALUE])) {
                        $data[self::VALUE] = '';
                    }
                    $data[self::VALUE] .= $node->textContent;
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
                        $nodeData[self::ATTRIBUTES][$attributeName] = $attribute->value;
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
     * @param array $data
     * @throws \Exception
     */
    private function createInstance(array &$data)
    {
        foreach ($data as $name => $list) {
            if (self::INSTANCE_ITEM === $name) {
                foreach ($list as $item) {
                    $class = trim($item[self::ATTRIBUTES]['class'], '\\');

                    $container = new InstanceClass(
                        $class,
                        $class,
                        $this->service->createArguments(isset($item['argument']) ? $item['argument'] : []),
                        isset($item[self::ATTRIBUTES]['shared'])
                            ? $item[self::ATTRIBUTES]['shared'] === 'true'
                            : false
                    );

                    foreach (isset($item[self::CALLBACK_ITEM]) ? $item[self::CALLBACK_ITEM] : [] as $callback) {
                        $method = trim($callback[self::ATTRIBUTES]['method']);
                        $container->addCallback(
                            $method,
                            $this->service->createArguments(isset($callback['argument']) ? $callback['argument'] : [])
                        );
                    }

                    $this->convertedData[$container->getId()] = $container;
                }
            }
        }
    }

    /**
     * @param array $data
     */
    private function createPreference(array &$data)
    {
        foreach ($data as $name => $list) {
            if (self::INTERFACE_ITEM === $name) {
                foreach ($list as $item) {
                    $class = trim($item[self::ATTRIBUTES]['class'], '\\');
                    $interface = trim($item[self::ATTRIBUTES]['name'], '\\');

                    if (isset($this->convertedData[$class])) {
                        $container = & $this->convertedData[$class];
                    } else {
                        $container = new InstanceClass($interface, $class);
                    }
                    $this->convertedData[$interface] = $container;
                }
            }
        }
    }
}

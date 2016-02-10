<?php
namespace Core\Di\Config\Xml;

use Core\Di\InstanceClass;
use Core\Config\ConverterInterface;
use Core\Di\Config\Argument\TypeFactoryInterface;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    const INTERFACE_ITEM = 'interface';

    const INSTANCE_ITEM = 'instance';

    const ARGUMENT_ITEM = 'argument';

    const ROOT_ITEM = 'config';

    const ATTRIBUTES = '@attributes';

    const VALUE = '@value';

    /**
     * @var TypeFactoryInterface
     */
    protected $typeFactory;

    /**
     * @var InstanceClass[]
     */
    protected $convertedData = [];

    /**
     * Constructor
     *
     * @param TypeFactoryInterface $typeFactory
     */
    public function __construct(TypeFactoryInterface $typeFactory)
    {
        $this->typeFactory = $typeFactory;
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
    protected function toArray(\DOMNode $node, array &$data)
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
    protected function createInstance(array &$data)
    {
        foreach ($data as $name => $list) {
            if (self::INSTANCE_ITEM === $name) {
                foreach ($list as $item) {
                    $class = trim($item[self::ATTRIBUTES]['class'], '\\');
                    $instanceArguments = [];
                    $arguments = isset($item['argument']) ? $item['argument'] : [];
                    foreach ($arguments as $argument) {
                        $attributes = $argument[self::ATTRIBUTES];
                        unset($argument[self::ATTRIBUTES]);
                        $type = key($argument);
                        $argument = reset($argument);
                        $argument = reset($argument);
                        $instanceArguments[$attributes['name']] = $this->typeFactory->create($type)
                            ->convert($argument);;
                    }
                    $container = new InstanceClass(
                        $class,
                        $class,
                        $instanceArguments,
                        isset($item[self::ATTRIBUTES]['shared'])
                            ? $item[self::ATTRIBUTES]['shared'] === 'true'
                            : false
                    );
                    $this->convertedData[$container->getId()] = $container;
                }
            }
        }
    }

    /**
     * @param array $data
     */
    protected function createPreference(array &$data)
    {
        sleep(0);
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

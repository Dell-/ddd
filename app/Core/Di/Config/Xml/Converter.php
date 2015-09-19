<?php
namespace Core\Di\Config\Xml;

use Core\Di\InstanceClass;
use Core\Config\ReaderInterface;
use Core\Config\ConverterInterface;
use Core\Di\Config\Argument\TypeFactory;
use Core\Di\Config\Xml\Reader as DiReader;

/**
 * Class Converter
 */
class Converter implements ConverterInterface
{
    const PREFERENCE_ITEM = 'interface';

    const INSTANCE_ITEM = 'instance';

    const ARGUMENT_ITEM = 'argument';

    /**
     * @var TypeFactory
     */
    protected $typeFactory;

    /**
     * @var InstanceClass[]
     */
    protected $instances = [];

    /**
     * Constructor
     *
     * @param TypeFactory $typeFactory
     */
    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    /**
     * @param ReaderInterface $reader
     * @return array
     */
    public function convert(ReaderInterface $reader)
    {
        $rawConfig = $reader->read();

        $this->createInstance($rawConfig);
        $this->createPreference($rawConfig);

        return $this->instances;
    }

    /**
     * @param array $rawConfig
     * @throws \Exception
     */
    protected function createInstance(array &$rawConfig)
    {
        foreach ($rawConfig as $name => $nodeList) {
            if (static::INSTANCE_ITEM === $name) {
                foreach ($nodeList as $node) {
                    $class = trim($node[DiReader::ATTRIBUTES]['class'], '\\');
                    $instanceArguments = [];
                    $arguments = isset($node['argument']) ? $node['argument'] : [];
                    foreach ($arguments as $argument) {
                        $attributes = $argument[DiReader::ATTRIBUTES];
                        unset($argument[DiReader::ATTRIBUTES]);
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
                        isset($node[DiReader::ATTRIBUTES]['shared'])
                            ? $node[DiReader::ATTRIBUTES]['shared'] === 'true'
                            : false
                    );
                    $this->instances[$container->getId()] = $container;
                }
            }
        }
    }

    /**
     * @param array $rawConfig
     */
    protected function createPreference(array &$rawConfig)
    {
        foreach ($rawConfig as $name => $nodeList) {
            if (static::PREFERENCE_ITEM === $name) {
                foreach ($nodeList as $node) {
                    $class = trim($node[DiReader::ATTRIBUTES]['class'], '\\');
                    $interface = trim($node[DiReader::ATTRIBUTES]['name'], '\\');

                    if (isset($this->instances[$class])) {
                        $container = $this->instances[$class];
                    } else {
                        $container = new InstanceClass($interface, $class);
                    }
                    $this->instances[$interface] = $container;
                }
            }
        }
    }
}

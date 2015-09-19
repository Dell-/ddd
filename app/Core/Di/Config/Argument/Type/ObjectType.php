<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\InstanceClass;
use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Reader as DiReader;

/**
 * Class ObjectType
 */
class ObjectType extends AbstractType implements TypeConverterInterface
{
    /**
     * @param array $node
     * @return mixed
     */
    public function convert(array $node)
    {
        $instanceArguments = [];
        $arguments = isset($node['argument']) ? $node['argument'] : [];
        $class = trim($node[DiReader::ATTRIBUTES]['class'], '\\');
        foreach ($arguments as $argument) {
            $attributes = $argument[DiReader::ATTRIBUTES];
            unset($argument[DiReader::ATTRIBUTES]);
            $type = key($argument);
            $argument = reset($argument);
            $argument = reset($argument);
            $instanceArguments[$attributes['name']] = $this->typeFactory->create($type)
                ->convert($argument);
        }

        return new InstanceClass($class, $class, $instanceArguments);
    }
}

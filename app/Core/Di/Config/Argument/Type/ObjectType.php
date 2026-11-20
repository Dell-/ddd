<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Xml\Converter;
use Core\Di\InstanceClass;

/**
 * <object class="Some\Class"><argument .../></object> => InstanceClass, created by the container.
 */
class ObjectType extends AbstractType
{
    public function convert(array $node): InstanceClass
    {
        $class = trim($node[Converter::ATTRIBUTES]['class'], '\\');

        $arguments = [];
        foreach ($node['argument'] ?? [] as $argument) {
            $arguments[$argument[Converter::ATTRIBUTES]['name']] = $this->typeFactory->convertValue($argument);
        }

        return new InstanceClass($class, $class, $arguments);
    }
}

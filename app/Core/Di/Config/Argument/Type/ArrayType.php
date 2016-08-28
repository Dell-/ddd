<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Converter;

/**
 * Class ArrayType
 */
class ArrayType extends AbstractType implements TypeConverterInterface
{
    /**
     * @param array $node
     * @return mixed
     */
    public function convert(array $node)
    {
        $result = [];
        foreach ($node['item'] as $item) {
            if (isset($item[Converter::ATTRIBUTES]['name'])) {
                $name = $item[Converter::ATTRIBUTES]['name'];
                unset($item[Converter::ATTRIBUTES]);
                $type = key($item);
                $item = reset($item);
                $item = reset($item);
                $result[$name] = $this->typeFactory->create($type)->convert($item);
            } else {
                $type = key($item);
                $item = reset($item);
                $item = reset($item);
                $result[] = $this->typeFactory->create($type)->convert($item);
            }
        }

        return $result;
    }
}

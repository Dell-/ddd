<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Reader as DiReader;

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
            if (isset($item[DiReader::ATTRIBUTES]['name'])) {
                $name = $item[DiReader::ATTRIBUTES]['name'];
                unset($item[DiReader::ATTRIBUTES]);
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

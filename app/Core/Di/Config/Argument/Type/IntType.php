<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Converter;

/**
 * Class IntType
 */
class IntType extends AbstractType implements TypeConverterInterface
{
    /**
     * @param array $node
     * @return mixed
     */
    public function convert(array $node)
    {
        return (int) $node[Converter::VALUE];
    }
}

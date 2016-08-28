<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Converter;

/**
 * Class BoolType
 */
class BoolType extends AbstractType implements TypeConverterInterface
{
    /**
     * @param array $node
     * @return mixed
     */
    public function convert(array $node)
    {
        return $node[Converter::VALUE] === 'true' || $node[Converter::VALUE] === '1';
    }
}

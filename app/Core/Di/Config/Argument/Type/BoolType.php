<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Reader as DiReader;

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
        return $node[DiReader::VALUE] === 'true' || $node[DiReader::VALUE] === '1';
    }
}

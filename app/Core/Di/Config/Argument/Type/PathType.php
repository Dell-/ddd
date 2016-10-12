<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeConverterInterface;
use Core\Di\Config\Xml\Converter;

/**
 * Class StringType
 */
class PathType extends AbstractType implements TypeConverterInterface
{
    /**
     * @param array $node
     * @return string
     */
    public function convert(array $node)
    {
        $const = $node[Converter::ATTRIBUTES]['base'];
        if (!defined($const)) {
            throw new \InvalidArgumentException('The "' . $const .  '" constant does not exists.');
        }

        return constant($const) . '/' . trim($node[Converter::VALUE], DS);
    }
}

<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Xml\Converter;

/**
 * <path base="CONSTANT">relative/path</path> => value of CONSTANT + "/relative/path".
 */
class PathType extends AbstractType
{
    #[\Override]
    public function convert(array $node): string
    {
        $const = $node[Converter::ATTRIBUTES]['base'] ?? '';
        if (!defined($const)) {
            throw new \InvalidArgumentException('The "' . $const . '" constant does not exist.');
        }

        return rtrim((string) constant($const), '/\\') . DS . trim($this->value($node), '/\\');
    }
}

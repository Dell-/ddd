<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Xml\Converter;

/**
 * <array><item name="key"><string>value</string></item>...</array>
 * Items without a name get numeric keys.
 */
class ArrayType extends AbstractType
{
    /**
     * @return array<array-key, mixed>
     */
    public function convert(array $node): array
    {
        $result = [];
        foreach ($node['item'] ?? [] as $item) {
            $value = $this->typeFactory->convertValue($item);
            $name = $item[Converter::ATTRIBUTES]['name'] ?? null;
            if ($name === null) {
                $result[] = $value;
            } else {
                $result[$name] = $value;
            }
        }

        return $result;
    }
}

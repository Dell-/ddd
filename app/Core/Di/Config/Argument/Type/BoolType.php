<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

class BoolType extends AbstractType
{
    public function convert(array $node): bool
    {
        return in_array(strtolower($this->value($node)), ['true', '1'], true);
    }
}

<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

class FloatType extends AbstractType
{
    public function convert(array $node): float
    {
        return (float) $this->value($node);
    }
}

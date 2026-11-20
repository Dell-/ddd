<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

class IntType extends AbstractType
{
    public function convert(array $node): int
    {
        return (int) $this->value($node);
    }
}

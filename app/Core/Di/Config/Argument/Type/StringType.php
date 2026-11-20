<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Xml\Converter;

class StringType extends AbstractType
{
    #[\Override]
    public function convert(array $node): string
    {
        return (string) ($node[Converter::VALUE] ?? '');
    }
}

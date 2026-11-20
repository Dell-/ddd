<?php

declare(strict_types=1);

namespace Core\Config;

interface ConverterInterface
{
    /**
     * @return array<array-key, mixed>
     */
    public function convert(\DOMDocument $document): array;
}

<?php

declare(strict_types=1);

namespace Core\Config;

interface ReaderInterface
{
    public function read(): \DOMDocument;
}

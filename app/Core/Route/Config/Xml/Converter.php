<?php

declare(strict_types=1);

namespace Core\Route\Config\Xml;

use Core\Config\ConverterInterface;
use Core\Config\Xml\DomToArray;

/**
 * Converts routes.xml into [METHOD => [route name => action class]].
 */
class Converter implements ConverterInterface
{
    public function __construct(private readonly DomToArray $domToArray = new DomToArray())
    {
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function convert(\DOMDocument $document): array
    {
        $routes = [];
        foreach ($this->domToArray->convert($document)['route'] ?? [] as $route) {
            $attributes = $route[DomToArray::ATTRIBUTES];
            $routes[strtoupper($attributes['method'])][$attributes['name']] = trim($attributes['action'], '\\');
        }

        return $routes;
    }
}

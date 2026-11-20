<?php

declare(strict_types=1);

namespace Core\Route\Config\Xml;

use Core\Config\ConverterInterface;
use Core\Config\Xml\DomToArray;
use Core\Route\QueryParameter;
use Core\Route\Route;
use Core\Route\TrailingSlash;

/**
 * Converts routes.xml into ['routes' => [name => Route], 'commands' => [name => action class]].
 *
 *   <routes trailing-slash="redirect">      redirect (default) | strict | ignore, see TrailingSlash
 *
 *   <route name="item.list" method="GET" path="/items" action="Action\Class">
 *       <query name="page" requirement="\d+" default="1"/>
 *   </route>
 *   <route name="item.show" method="GET" path="/items/{id:\d+}" action="Action\Class"/>
 *   <route name="item.update" method="PUT PATCH" path="/items/{id:\d+}" action="Action\Class"/>
 *   <command name="hello" action="Action\Class"/>
 */
class Converter implements ConverterInterface
{
    public function __construct(private readonly DomToArray $domToArray = new DomToArray())
    {
    }

    /**
     * @return array{routes: array<string, Route>, commands: array<string, string>}
     */
    #[\Override]
    public function convert(\DOMDocument $document): array
    {
        $data = $this->domToArray->convert($document);
        $default = self::trailingSlash($data[DomToArray::ATTRIBUTES]['trailing-slash'] ?? null, TrailingSlash::DEFAULT);

        $routes = [];
        foreach ($data['route'] ?? [] as $item) {
            $attributes = $item[DomToArray::ATTRIBUTES];
            $name = $attributes['name'];
            if (isset($routes[$name])) {
                throw new \InvalidArgumentException(sprintf('Duplicate route name "%s".', $name));
            }
            $routes[$name] = new Route(
                $name,
                preg_split('/\s+/', trim($attributes['method']), -1, PREG_SPLIT_NO_EMPTY) ?: [],
                $attributes['path'],
                trim($attributes['action'], '\\'),
                array_map(static fn (array $query): QueryParameter => new QueryParameter(
                    $query[DomToArray::ATTRIBUTES]['name'],
                    $query[DomToArray::ATTRIBUTES]['requirement'] ?? null,
                    $query[DomToArray::ATTRIBUTES]['default'] ?? null,
                    ($query[DomToArray::ATTRIBUTES]['required'] ?? 'false') === 'true'
                ), $item['query'] ?? []),
                self::trailingSlash($attributes['trailing-slash'] ?? null, $default)
            );
        }

        $commands = [];
        foreach ($data['command'] ?? [] as $item) {
            $attributes = $item[DomToArray::ATTRIBUTES];
            $commands[$attributes['name']] = trim($attributes['action'], '\\');
        }

        return ['routes' => $routes, 'commands' => $commands];
    }

    private static function trailingSlash(?string $value, TrailingSlash $default): TrailingSlash
    {
        return $value === null
            ? $default
            : TrailingSlash::tryFrom($value) ?? throw new \InvalidArgumentException(sprintf(
                'Invalid trailing-slash "%s", expected one of: %s.',
                $value,
                implode(', ', array_column(TrailingSlash::cases(), 'value'))
            ));
    }
}

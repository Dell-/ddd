<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument;

use Core\Di\Config\Argument\Type\ArrayType;
use Core\Di\Config\Argument\Type\BoolType;
use Core\Di\Config\Argument\Type\FloatType;
use Core\Di\Config\Argument\Type\IntType;
use Core\Di\Config\Argument\Type\ObjectType;
use Core\Di\Config\Argument\Type\PathType;
use Core\Di\Config\Argument\Type\StringType;
use Core\Di\Config\Xml\Converter;

class TypeFactory implements TypeFactoryInterface
{
    /**
     * @var array<string, class-string<TypeConverterInterface>>
     */
    private array $types = [
        'object' => ObjectType::class,
        'array' => ArrayType::class,
        'string' => StringType::class,
        'float' => FloatType::class,
        'int' => IntType::class,
        'bool' => BoolType::class,
        'path' => PathType::class,
    ];

    /**
     * @var array<string, TypeConverterInterface>
     */
    private array $instances = [];

    public function create(string $type): TypeConverterInterface
    {
        if (!isset($this->types[$type])) {
            throw new \InvalidArgumentException(sprintf('Undefined type (%s) for converter instance', $type));
        }

        return $this->instances[$type] ??= new $this->types[$type]($this);
    }

    public function convertValue(array $node): mixed
    {
        unset($node[Converter::ATTRIBUTES], $node[Converter::VALUE]);

        $type = array_key_first($node);
        if ($type === null || !is_array($node[$type]) || $node[$type] === []) {
            throw new \InvalidArgumentException('Argument value is missing.');
        }

        return $this->create((string) $type)->convert(reset($node[$type]));
    }
}

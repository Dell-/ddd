<?php
namespace Core\Di\Config\Argument;

use Core\Di\Config\Argument\Type\ArrayType;
use Core\Di\Config\Argument\Type\BoolType;
use Core\Di\Config\Argument\Type\FloatType;
use Core\Di\Config\Argument\Type\IntType;
use Core\Di\Config\Argument\Type\ObjectType;
use Core\Di\Config\Argument\Type\PathType;
use Core\Di\Config\Argument\Type\StringType;

/**
 * Class TypeFactory
 */
class TypeFactory implements TypeFactoryInterface
{
    /**
     * @var array
     */
    private $types = [
        'object' => ObjectType::class,
        'array' => ArrayType::class,
        'string' => StringType::class,
        'float' => FloatType::class,
        'int' => IntType::class,
        'bool' => BoolType::class,
        'path' => PathType::class,
    ];

    /**
     * @var TypeConverterInterface[]
     */
    protected $instances;

    /**
     * @inheritdoc
     */
    public function create($type)
    {
        if (!isset($this->types[$type])) {
            throw new \Exception(sprintf('Undefined type (%s) for converter instance', $type));
        }

        if (!isset($this->instances[$type])) {
            $this->instances[$type] = new $this->types[$type]($this);
        }

        return $this->instances[$type];
    }
}

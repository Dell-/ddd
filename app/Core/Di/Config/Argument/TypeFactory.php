<?php
namespace Core\Di\Config\Argument;

/**
 * Class TypeFactory
 */
class TypeFactory implements TypeFactoryInterface
{
    /**
     * @var array
     */
    protected $types = [
        'object' => 'Core\Di\Config\Argument\Type\ObjectType',
        'array' => 'Core\Di\Config\Argument\Type\ArrayType',
        'string' => 'Core\Di\Config\Argument\Type\StringType',
        'float' => 'Core\Di\Config\Argument\Type\FloatType',
        'int' => 'Core\Di\Config\Argument\Type\IntType',
        'bool' => 'Core\Di\Config\Argument\Type\BoolType',
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

<?php
namespace Core\Di\Config\Argument;

use Core\Di\Config\Xml\Converter;

/**
 * Class Service
 */
class Service
{
    /**
     * @var TypeFactoryInterface
     */
    private $typeFactory;

    /**
     * Constructor
     *
     * @param TypeFactoryInterface $typeFactory
     */
    public function __construct(TypeFactoryInterface $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function createArguments(array $arguments)
    {
        $instanceArguments = [];
        foreach ($arguments as $argument) {
            $attributes = $argument[Converter::ATTRIBUTES];
            unset($argument[Converter::ATTRIBUTES]);
            $type = key($argument);
            $argument = reset($argument);
            $argument = reset($argument);
            $instanceArguments[$attributes['name']] = $this->typeFactory->create($type)
                ->convert($argument);
        }

        return $instanceArguments;
    }
}

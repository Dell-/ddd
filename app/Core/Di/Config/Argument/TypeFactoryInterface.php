<?php
namespace Core\Di\Config\Argument;

/**
 * Interface TypeFactoryInterface
 */
interface TypeFactoryInterface
{
    /**
     * @param string $type
     * @return TypeConverterInterface
     */
    public function create($type);
}
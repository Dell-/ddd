<?php
namespace Core\Di\Config\Argument;

/**
 * Interface TypeConverterInterface
 */
interface TypeConverterInterface
{
    /**
     * @param array $node
     * @return mixed
     */
    public function convert(array $node);
}

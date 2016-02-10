<?php
namespace Core\Di;

use Core\Config\ReaderInterface;
use Core\Config\ConverterInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * Instances of classes
     *
     * @var InstanceClass[]
     */
    private $instances = [];

    /**
     * Constructor
     *
     * @param ReaderInterface $reader
     * @param ConverterInterface $converter
     */
    public function __construct(ReaderInterface $reader, ConverterInterface $converter)
    {
        $this->instances = $converter->convert($reader->read());
    }

    /**
     * Get instance configuration
     *
     * @param string $name
     * @return InstanceClass|null
     */
    public function getInstance($name)
    {
        return isset($this->instances[$name]) ? $this->instances[$name] : null;
    }

    /**
     * Has exist instance
     *
     * @param string $name
     * @return bool
     */
    public function hasInstance($name)
    {
        return isset($this->instances[$name]);
    }
}

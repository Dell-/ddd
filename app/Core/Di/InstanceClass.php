<?php
namespace Core\Di;

/**
 * Class InstanceClass
 */
class InstanceClass
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $class;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @var bool
     */
    private $shared;

    /**
     * @var array
     */
    private $callbacks = [];

    /**
     * Constructor
     *
     * @param string $id
     * @param string $class
     * @param array $arguments
     * @param bool $shared
     */
    public function __construct($id, $class, array $arguments = [], $shared = false)
    {
        $this->id = $id;
        $this->class = $class;
        $this->arguments = $arguments;
        $this->shared = $shared;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return bool
     */
    public function isShared()
    {
        return $this->shared;
    }

    /**
     * @param string $method
     * @param array $arguments
     */
    public function addCallback($method, array $arguments = [])
    {
        $this->callbacks[] = [
            'method' => $method,
            'arguments' => $arguments
        ];
    }

    /**
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }
}

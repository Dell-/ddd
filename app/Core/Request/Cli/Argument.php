<?php
namespace Core\Request\Cli;

use Core\Request\RequestInterface;

/**
 * Class Argument
 */
class Argument implements RequestInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function getData($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}

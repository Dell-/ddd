<?php
namespace Core\Request\Http;

/**
 * Class Argument
 */
class Argument implements \Core\Request\RequestInterface
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

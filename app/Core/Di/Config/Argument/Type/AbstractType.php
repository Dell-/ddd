<?php
namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Argument\TypeFactory;

/**
 * Class AbstractType
 */
abstract class AbstractType
{
    /**
     * @var TypeFactory
     */
    protected $typeFactory;

    /**
     * Constructor
     *
     * @param TypeFactory $typeFactory
     */
    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }
}

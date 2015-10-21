<?php
namespace Core\Route;

use Core\Config\ReaderInterface;

/**
 * Class Dispatcher
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * Constructor
     *
     * @param ReaderInterface $reader
     */
    public function __construct(ReaderInterface $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @inheritdoc
     */
    public function dispatch()
    {

    }
}

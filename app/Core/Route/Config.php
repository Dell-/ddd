<?php
namespace Core\Route;

use Core\Config\ReaderInterface;
use Core\Config\ConverterInterface;

/**
 * Class Config
 */
class Config
{
    /**
     * @var ReaderInterface
     */
    private $reader;

    /**
     * @var ConverterInterface
     */
    private $converter;

    /**
     * Constructor
     *
     * @param ReaderInterface $reader
     * @param ConverterInterface $converter
     */
    public function __construct(ReaderInterface $reader, ConverterInterface $converter)
    {
        $this->reader = $reader;
        $this->converter = $converter;
    }
}

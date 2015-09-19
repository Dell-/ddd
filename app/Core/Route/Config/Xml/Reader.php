<?php
namespace Core\Route\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Config\Xml\AbstractReader;
use Core\Filesystem\RegexIteratorFactory;

/**
 * Class Reader
 */
class Reader extends AbstractReader implements ReaderInterface
{
    const ROOT_NAME = 'routes';

    protected $test;

    public function __construct($routerConfigReader)
    {
        $this->test = $routerConfigReader;
    }

    /**
     * @param string $directory
     * @return array
     */
    protected function getFiles($directory)
    {
        return $this->file->read([$directory], new RegexIteratorFactory('#etc/routes\.xml$#'));
    }
}

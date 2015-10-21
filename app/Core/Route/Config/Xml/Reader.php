<?php
namespace Core\Route\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Config\Xml\AbstractReader;

/**
 * Class Reader
 */
class Reader extends AbstractReader implements ReaderInterface
{
    const ROOT_NAME = 'routes';

    /**
     * @param string $directory
     * @return array
     */
    protected function getFiles($directory)
    {
        return $this->filesystemReader->read([$directory]);
    }
}

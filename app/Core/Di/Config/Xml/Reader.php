<?php
namespace Core\Di\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Config\Xml\AbstractReader;
use Core\Filesystem\RegexIteratorFactory;

/**
 * Class Reader
 */
class Reader extends AbstractReader implements ReaderInterface
{
    const ROOT_NAME = 'di';

    /**
     * @param string $directory
     * @return array
     */
    protected function getFiles($directory)
    {
        return $this->file->read([BP . $directory], new RegexIteratorFactory('#etc/di\.xml$#'));
    }
}

<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Class FileReader
 */
class Reader implements ReaderInterface
{
    /**
     * @inheritdoc
     */
    public function read(FileInterface $file)
    {
        $content = false;
        if ($file->isReadable()) {
            $content = file_get_contents($file->getPathname());
        }

        if ($content === false) {
            throw new \RuntimeException("The file '{$file->getPathname()}' cannot be read.");
        }

        return $content;
    }
}

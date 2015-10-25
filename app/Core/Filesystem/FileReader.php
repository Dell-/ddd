<?php
namespace Core\Filesystem;

/**
 * Class FileReader
 */
class FileReader implements FileReaderInterface
{
    /**
     * @inheritdoc
     */
    public function read(FileInterface $file)
    {
        if (!$file->isReadable()) {
            throw new \Exception("The file '{$file->getPathname()}' cannot be read.");
        }

        return file_get_contents($file->getPathname());
    }
}

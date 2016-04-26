<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;

/**
 * Class FileReader
 */
class Reader implements ReaderInterface
{
    /**
     * @var int
     */
    private $maxLen;

    /**
     * @var int
     */
    private $offset;

    /**
     * @inheritdoc
     */
    public function setMaxLen($maxLen)
    {
        $this->maxLen = (int) $maxLen;
    }

    /**
     * @inheritdoc
     */
    public function setOffset($offset)
    {
        $this->offset = (int) $offset;
    }

    /**
     * @inheritdoc
     */
    public function read(FileInterface $file)
    {
        $content = false;
        if ($file->isReadable()) {
            $content = file_get_contents($file->getPathname(), null, null, $this->offset, $this->maxLen);
        }

        if ($content === false) {
            throw new \RuntimeException("The file '{$file->getPathname()}' cannot be read.");
        }

        return $content;
    }
}

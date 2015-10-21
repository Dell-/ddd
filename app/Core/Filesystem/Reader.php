<?php
namespace Core\Filesystem;

/**
 * Class Reader
 */
class Reader
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var FileCollector
     */
    private $fileCollector;

    /**
     * Constructor
     *
     * @param string $pattern
     * @param FileCollector $fileCollector
     */
    public function __construct($pattern, FileCollector $fileCollector)
    {
        $this->pattern = $pattern;
        $this->fileCollector = $fileCollector;
    }

    /**
     * Read data
     *
     * @param array $path
     * @return array
     * @throws \Exception
     */
    public function read(array $path)
    {
        $result = [];
        foreach ($path as $item) {
            $files = $this->fileCollector->collect($item, $this->pattern);
            foreach ($files as $file) {
                if (!$file->isReadable()) {
                    throw new \Exception("The file '{$file->getPathname()}' cannot be read.");
                }
                $result[$file->getPathname()] = file_get_contents($file->getPathname());
            }
        }

        return $result;
    }
}

<?php
namespace Core\Filesystem;

/**
 * Class FileCollector
 */
class FileCollector
{
    /**
     * @var IteratorFactory
     */
    private $iteratorFactory;

    /**
     * Constructor
     *
     * @param IteratorFactory $iteratorFactory
     */
    public function __construct(IteratorFactory $iteratorFactory)
    {
       $this->iteratorFactory = $iteratorFactory;
    }

    /**
     * Read files in the directory
     *
     * @param string $path
     * @param string $pattern
     * @return FileInterface[]
     */
    public function collect($path, $pattern)
    {
        $files = [];
        $iterator = $this->iteratorFactory->create($path, $pattern);
        /** @var \SplFileInfo $file */
        foreach ($iterator as $key => $file) {
            if ($file->isFile()) {
                $files[$key] = $file;
            }
        }

        return $files;
    }
}

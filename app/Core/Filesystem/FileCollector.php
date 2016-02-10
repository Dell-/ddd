<?php
namespace Core\Filesystem;

/**
 * Class FileCollector
 */
class FileCollector implements FileCollectorInterface
{
    /**
     * Iterator factory
     *
     * @var IteratorFactory
     */
    private $iteratorFactory;

    /**
     * @var FilterIterator
     */
    private $filterIterator;

    /**
     * Constructor
     *
     * @param IteratorFactory $iteratorFactory
     * @param FilterIterator $filterIterator
     */
    public function __construct(IteratorFactory $iteratorFactory, FilterIterator $filterIterator)
    {
        $this->iteratorFactory = $iteratorFactory;
        $this->filterIterator = $filterIterator;
    }

    /**
     * @inheritdoc
     */
    public function collect(DirectoryInterface $directory)
    {
        $files = [];
        $iterator = $this->iteratorFactory->create($directory->getPathname());
        foreach ($this->filterIterator->filter($iterator) as $key => $file) {
            if ($file->isFile()) {
                $files[$key] = new File($file);
            }
        }

        return $files;
    }
}

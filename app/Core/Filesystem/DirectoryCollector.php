<?php
namespace Core\Filesystem;

/**
 * Class DirectoryCollector
 */
class DirectoryCollector implements DirectoryCollectorInterface
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
        $directories = [];
        $iterator = $this->iteratorFactory->create($directory->getPathname());
        foreach ($this->filterIterator->filter($iterator) as $key => $innerDirectory) {
            if ($innerDirectory->isDir()) {
                $directories[$key] = new Directory($innerDirectory);
            }
        }

        return $directories;
    }
}

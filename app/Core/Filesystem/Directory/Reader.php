<?php
namespace Core\Filesystem\Directory;

use Core\CollectionInterface;
use Core\Filesystem\DirectoryInterface;

/**
 * Class Reader
 */
class Reader implements ReaderInterface
{
    /**
     * @var CollectionInterface
     */
    private $collection;

    /**
     * Constructor
     *
     * @param CollectionInterface $collection
     */
    public function __construct(CollectionInterface $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @inheritdoc
     */
    public function read(DirectoryInterface $directory)
    {
        // TODO: Implement read() method.
    }
}

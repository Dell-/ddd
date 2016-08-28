<?php
namespace Core\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Filesystem\Content\Xml\Dom\Merger;
use Core\Filesystem\Directory;
use Core\Filesystem\File;
use Core\Filesystem\Filter\File as FilterFile;
use Core\Filesystem\Filter\Regexp as FilterRegexp;
use Core\Filesystem\IteratorFactory;

/**
 * Class Reader
 */
class Reader implements ReaderInterface
{
    /**
     * @var Merger
     */
    private $domMerger;

    /**
     * @var Directory\Factory
     */
    private $directoryFactory;

    /**
     * @var File\ReaderInterface
     */
    private $fileReader;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var IteratorFactory
     */
    private $iteratorFactory;

    /**
     * Reader constructor.
     *
     * @param string $directory
     * @param Directory\Factory $directoryFactory
     * @param IteratorFactory $iteratorFactory
     * @param File\ReaderInterface $fileReader
     * @param Merger $domMerger
     */
    public function __construct(
        $directory,
        Directory\Factory $directoryFactory,
        IteratorFactory $iteratorFactory,
        File\ReaderInterface $fileReader,
        Merger $domMerger
    ) {
        $this->directory = $directory;
        $this->directoryFactory = $directoryFactory;
        $this->fileReader = $fileReader;
        $this->domMerger = $domMerger;
        $this->iteratorFactory = $iteratorFactory;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $directory = $this->directoryFactory->create($this->directory);
        $iterator = $this->iteratorFactory->create($directory);

        /** @var File $file */
        foreach (new FilterFile(new FilterRegexp($iterator, '#di\.xml$#')) as $file) {
            $this->domMerger->merge($this->fileReader->read($file));
        }

        return $this->domMerger->getDOMDocument();
    }
}

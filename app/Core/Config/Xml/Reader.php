<?php
namespace Core\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Filesystem\DirectoryFactory;
use Core\Filesystem\FileReaderInterface;
use Core\Filesystem\FileCollectorInterface;
use Core\Filesystem\Content\Xml\Dom\Merger;

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
     * @var DirectoryFactory
     */
    private $directoryFactory;

    /**
     * @var FileReaderInterface
     */
    private $fileReader;

    /**
     * @var FileCollectorInterface
     */
    private $fileCollector;

    /**
     * @var string
     */
    private $directoryName;

    /**
     * Constructor
     *
     * @param string $directoryName
     * @param DirectoryFactory $directoryFactory
     * @param FileReaderInterface $fileReader
     * @param FileCollectorInterface $fileCollector
     * @param Merger $domMerger
     */
    public function __construct(
        $directoryName,
        DirectoryFactory $directoryFactory,
        FileReaderInterface $fileReader,
        FileCollectorInterface $fileCollector,
        Merger $domMerger
    ) {
        $this->directoryName = $directoryName;
        $this->directoryFactory = $directoryFactory;
        $this->fileReader = $fileReader;
        $this->fileCollector = $fileCollector;
        $this->domMerger = $domMerger;
    }

    /**
     * @inheritdoc
     */
    public function read()
    {
        $directory = $this->directoryFactory->create($this->directoryName);

        foreach ($this->fileCollector->collect($directory) as $file) {
            $this->domMerger->merge($this->fileReader->read($file));
        }

        return $this->domMerger->getDOMDocument();
    }
}

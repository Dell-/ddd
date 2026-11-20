<?php

declare(strict_types=1);

namespace Core\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Filesystem\Content\Xml\Dom\MergerFactory;
use Core\Filesystem\Directory;
use Core\Filesystem\File;
use Core\Filesystem\Filter\File as FileFilter;
use Core\Filesystem\Filter\Regexp as RegexpFilter;
use Core\Filesystem\IteratorFactory;

/**
 * Reads all XML files of a directory whose names match the pattern and merges them into one document.
 */
class Reader implements ReaderInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly Directory\FactoryInterface $directoryFactory,
        private readonly IteratorFactory $iteratorFactory,
        private readonly File\ReaderInterface $fileReader,
        private readonly MergerFactory $mergerFactory,
        private readonly string $pattern = '#di\.xml$#',
    ) {
    }

    public function read(): \DOMDocument
    {
        $iterator = $this->iteratorFactory->create($this->directoryFactory->create($this->directory));
        $merger = $this->mergerFactory->create();

        $files = iterator_to_array(new FileFilter(new RegexpFilter($iterator, $this->pattern)), false);
        // FilesystemIterator order is not defined: sort for a stable merge result
        usort($files, static fn (File $a, File $b): int => strcmp($a->getPathname(), $b->getPathname()));

        if ($files === []) {
            throw new \RuntimeException(sprintf(
                'No configuration files matching "%s" in "%s".',
                $this->pattern,
                $this->directory
            ));
        }

        foreach ($files as $file) {
            $merger->merge($this->fileReader->read($file));
        }

        return $merger->getDOMDocument();
    }
}

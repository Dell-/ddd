<?php

declare(strict_types=1);

namespace Core\Config\Xml;

use Core\Config\ReaderInterface;
use Core\Filesystem\Content\Xml\Dom\MergeRules;
use Core\Filesystem\Content\Xml\Dom\MergerFactory;
use Core\Filesystem\Directory;
use Core\Filesystem\File;
use Core\Filesystem\Filter\File as FileFilter;
use Core\Filesystem\Filter\Regexp as RegexpFilter;
use Core\Filesystem\IteratorFactory;

/**
 * Reads all XML files of a directory whose names match the pattern, validates each one
 * against the schema (if given) and merges them in file name order.
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
        private readonly ?string $schema = null,
        private readonly MergeRules $mergeRules = new MergeRules(),
    ) {
    }

    public function read(): \DOMDocument
    {
        $iterator = $this->iteratorFactory->create($this->directoryFactory->create($this->directory));
        $merger = $this->mergerFactory->create($this->mergeRules);

        $files = iterator_to_array(new FileFilter(new RegexpFilter($iterator, $this->pattern)), false);
        usort($files, $this->compareFiles(...));

        if ($files === []) {
            throw new \RuntimeException(sprintf(
                'No configuration files matching "%s" in "%s".',
                $this->pattern,
                $this->directory
            ));
        }

        foreach ($files as $file) {
            try {
                $merger->merge($this->fileReader->read($file), $this->schema);
            } catch (\InvalidArgumentException $e) {
                throw new \RuntimeException(
                    sprintf('Invalid configuration file "%s": %s', $file->getPathname(), $e->getMessage()),
                    0,
                    $e
                );
            }
        }

        return $merger->getDOMDocument();
    }

    /**
     * Merge order (later files override earlier ones): less specific names first, i.e. fewer dots
     * ("di.xml" < "local.di.xml" < "a.local.di.xml"), then alphabetical.
     */
    private function compareFiles(File $a, File $b): int
    {
        $nameA = $a->getFilename();
        $nameB = $b->getFilename();

        return [substr_count($nameA, '.'), $nameA] <=> [substr_count($nameB, '.'), $nameB];
    }
}

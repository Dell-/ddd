<?php
namespace Core\Filesystem;

/**
 * Class IteratorFactory
 */
class IteratorFactory
{
    /**
     * @var int
     */
    private $flags;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->flags = \FilesystemIterator::CURRENT_AS_FILEINFO
            | \FilesystemIterator::SKIP_DOTS
            | \FilesystemIterator::KEY_AS_FILENAME;
    }

    /**
     * Create files iterator
     *
     * @param string $path
     * @param string $pattern
     * @return \IteratorIterator
     */
    public function create($path, $pattern)
    {
        return new \RegexIterator(
            new \FilesystemIterator($path, $this->flags),
            $pattern,
            \RegexIterator::MATCH,
            \RegexIterator::USE_KEY
        );
    }
}

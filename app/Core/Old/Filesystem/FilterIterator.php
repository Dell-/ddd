<?php
namespace Core\Filesystem;

/**
 * Class FilterIterator
 */
class FilterIterator
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * Constructor
     *
     * @param string $pattern
     */
    public function __construct($pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * @param \FilesystemIterator $iterator
     * @return \RegexIterator
     */
    public function filter(\FilesystemIterator $iterator)
    {
        return new \RegexIterator(
            $iterator,
            $this->pattern,
            \RegexIterator::MATCH,
            \RegexIterator::USE_KEY
        );
    }
}

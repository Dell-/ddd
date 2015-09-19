<?php
namespace Core\Filesystem;

/**
 * Class RegexIteratorFactory
 */
class RegexIteratorFactory implements IteratorFactoryInterface
{
    /**
     * @var string $pattern
     */
    protected $pattern;

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
     * Create iterator
     *
     * @param \OuterIterator $outerIterator
     * @return \OuterIterator
     */
    public function create(\OuterIterator $outerIterator)
    {
        return new \RegexIterator($outerIterator, $this->pattern, \RegexIterator::GET_MATCH);
    }
}

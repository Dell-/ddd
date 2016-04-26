<?php
namespace Core\Filesystem\Directory;

use Core\Filesystem\CollectionInterface;
use Core\Filesystem\DirectoryInterface;

/**
 * Class Collection
 */
class Collection extends \ArrayObject implements CollectionInterface
{
    /**
     * Constructor
     *
     * @param DirectoryInterface[] $input
     */
    public function __construct(array $input)
    {
        array_map(function (DirectoryInterface $file) {}, $input);
        parent::__construct($input);
    }
}

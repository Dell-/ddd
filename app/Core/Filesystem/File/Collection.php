<?php
namespace Core\Filesystem\File;

use Core\Filesystem\FileInterface;
use Core\Filesystem\CollectionInterface;

/**
 * Class Collection
 */
class Collection extends \ArrayObject implements CollectionInterface
{
    /**
     * Constructor
     *
     * @param FileInterface[] $input
     */
    public function __construct(array $input)
    {
        array_map(function (FileInterface $file) {}, $input);
        parent::__construct($input);
    }
}

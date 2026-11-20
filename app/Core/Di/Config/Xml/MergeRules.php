<?php

declare(strict_types=1);

namespace Core\Di\Config\Xml;

use Core\Filesystem\Content\Xml\Dom\MergeRules as BaseMergeRules;

/**
 * di.xml merge rules: preferences are merged by interface name, instances by class,
 * arguments by name (the value is replaced as a whole). Callbacks are always appended.
 */
final class MergeRules extends BaseMergeRules
{
    public function __construct()
    {
        parent::__construct(
            [
                Converter::INTERFACE_ITEM => ['name'],
                Converter::INSTANCE_ITEM => ['class'],
                Converter::ARGUMENT_ITEM => ['name'],
            ],
            [Converter::ARGUMENT_ITEM]
        );
    }
}

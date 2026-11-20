<?php

declare(strict_types=1);

namespace Core\Route\Config\Xml;

use Core\Filesystem\Content\Xml\Dom\MergeRules as BaseMergeRules;

/**
 * routes.xml merge rules: a route is identified by its method and name.
 */
final class MergeRules extends BaseMergeRules
{
    public function __construct()
    {
        parent::__construct(['route' => ['method', 'name']]);
    }
}

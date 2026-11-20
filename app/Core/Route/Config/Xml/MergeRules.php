<?php

declare(strict_types=1);

namespace Core\Route\Config\Xml;

use Core\Filesystem\Content\Xml\Dom\MergeRules as BaseMergeRules;

/**
 * routes.xml merge rules: routes, their query parameters and commands are identified by name.
 */
final readonly class MergeRules extends BaseMergeRules
{
    public function __construct()
    {
        parent::__construct(['route' => ['name'], 'query' => ['name'], 'command' => ['name']]);
    }
}

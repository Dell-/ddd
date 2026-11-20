<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Merger is stateful, so every reader needs its own instance.
 */
class MergerFactory
{
    public function create(MergeRules $rules = new MergeRules()): Merger
    {
        return new Merger($rules);
    }
}

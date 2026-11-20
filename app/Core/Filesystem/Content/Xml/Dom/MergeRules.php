<?php

declare(strict_types=1);

namespace Core\Filesystem\Content\Xml\Dom;

/**
 * Describes how Merger matches and merges elements.
 *
 * - Key attributes: an element of the merged document is merged into the sibling of the base
 *   document with the same name and the same values of all key attributes. An element without
 *   key attributes configured (or without one of them) is appended.
 * - Replace content: the children of such an element are replaced as a whole instead of merged.
 */
readonly class MergeRules
{
    public const string ANY_ELEMENT = '*';

    /**
     * @param array<string, list<string>> $keyAttributes element local name (or "*") => key attribute names
     * @param list<string> $replaceContent local names of elements whose content is replaced
     */
    public function __construct(
        private array $keyAttributes = [self::ANY_ELEMENT => ['name']],
        private array $replaceContent = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function getKeyAttributes(\DOMElement $element): array
    {
        return $this->keyAttributes[$element->localName] ?? $this->keyAttributes[self::ANY_ELEMENT] ?? [];
    }

    public function isReplaceContent(\DOMElement $element): bool
    {
        return in_array($element->localName, $this->replaceContent, true);
    }
}

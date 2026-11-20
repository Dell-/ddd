<?php

declare(strict_types=1);

namespace Core\Route;

/**
 * A query string parameter declared by a route:
 *
 *     <query name="page" requirement="\d+" default="1"/>
 *     <query name="q" required="true"/>
 *
 * The router validates it (400 on a mismatch), adds the default and generate() puts it into the query string.
 */
final readonly class QueryParameter
{
    public function __construct(
        public string $name,
        public ?string $requirement = null,
        public ?string $default = null,
        public bool $required = false,
    ) {
        if ($requirement !== null && @preg_match('#^(?:' . $requirement . ')$#Du', '') === false) {
            throw new \InvalidArgumentException(sprintf('Invalid regex "%s" of query parameter "%s".', $requirement, $name));
        }
    }

    /**
     * Error message for an invalid value, null if it is valid. Lists ("tags[]=a&tags[]=b") are checked item by item.
     */
    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $this->required ? 'is required' : null;
        }
        if ($this->requirement === null) {
            return null;
        }

        $values = is_array($value) ? $value : [$value];
        foreach ($values as $item) {
            if (!is_string($item) || !preg_match('#^(?:' . $this->requirement . ')$#Du', $item)) {
                return sprintf('must match "%s"', $this->requirement);
            }
        }

        return null;
    }
}

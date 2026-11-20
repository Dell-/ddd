<?php

declare(strict_types=1);

namespace Core\Di\Config\Argument\Type;

use Core\Di\Config\Xml\Converter;

/**
 * Value of an environment variable (12-factor configuration):
 *
 *     <env name="APP_SECRET"/>                     required, string
 *     <env name="APP_DEBUG" type="bool" default="false"/>
 *     <env name="MAX_UPLOAD" type="int" default="10485760"/>
 */
final class EnvType extends AbstractType
{
    #[\Override]
    public function convert(array $node): string|int|float|bool
    {
        $attributes = $node[Converter::ATTRIBUTES] ?? [];
        $name = $attributes['name'] ?? throw new \InvalidArgumentException('<env> requires a name.');

        $value = getenv($name);
        if ($value === false || $value === '') {
            $value = $attributes['default'] ?? throw new \InvalidArgumentException(sprintf('Environment variable %s is not set.', $name));
        }

        return match ($attributes['type'] ?? 'string') {
            'string' => $value,
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? (int) $value
                : throw new \InvalidArgumentException(sprintf('%s must be an integer.', $name)),
            'float' => is_numeric($value) ? (float) $value : throw new \InvalidArgumentException(sprintf('%s must be a number.', $name)),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                ?? throw new \InvalidArgumentException(sprintf('%s must be a boolean.', $name)),
            default => throw new \InvalidArgumentException(sprintf('Unknown <env> type "%s".', $attributes['type'])),
        };
    }
}

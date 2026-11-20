<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Field value syntax of RFC 9110 §5.6: lists, tokens, quoted strings and parameters.
 */
final class HeaderValue
{
    /**
     * RFC 9110 §5.6.2 token.
     */
    public const string TOKEN = "[!#$%&'*+\\-.^_`|~0-9A-Za-z]+";

    private function __construct()
    {
    }

    public static function isToken(string $value): bool
    {
        return (bool) preg_match('/^' . self::TOKEN . '$/D', $value);
    }

    /**
     * Splits a comma separated list (RFC 9110 §5.6.1), respecting quoted strings; empty elements are dropped.
     *
     * @return list<string>
     */
    public static function splitList(string $value): array
    {
        return self::split($value, ',');
    }

    /**
     * Splits "value; name=token; name="quoted"" into the value and lower-cased parameters (RFC 9110 §5.6.6).
     *
     * @return array{string, array<string, string>}
     */
    public static function parseParameters(string $value): array
    {
        $parts = self::split($value, ';');
        $main = trim((string) array_shift($parts));

        $parameters = [];
        foreach ($parts as $part) {
            $position = strpos($part, '=');
            if ($position === false) {
                continue;
            }
            $name = strtolower(trim(substr($part, 0, $position)));
            if (!self::isToken($name)) {
                continue;
            }
            $parameters[$name] ??= self::unquote(trim(substr($part, $position + 1)));
        }

        return [$main, $parameters];
    }

    /**
     * Value of a quoted-string without the quotes and escapes, other values unchanged.
     *
     * Only \" and \\ are treated as quoted-pairs: browsers do not escape backslashes in multipart
     * file names (WHATWG HTML §4.10.21.8), so "C:\dir\file.txt" must keep them.
     */
    public static function unquote(string $value): string
    {
        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            return (string) preg_replace('/\\\\(["\\\\])/', '$1', substr($value, 1, -1));
        }

        return $value;
    }

    /**
     * The value as a token if possible, otherwise as a quoted-string.
     */
    public static function quote(string $value): string
    {
        if (self::isToken($value)) {
            return $value;
        }

        return '"' . addcslashes($value, '"\\') . '"';
    }

    /**
     * @return list<string>
     */
    private static function split(string $value, string $separator): array
    {
        $items = [];
        $current = '';
        $quoted = false;
        $length = strlen($value);
        for ($i = 0; $i < $length; $i++) {
            $char = $value[$i];
            if ($quoted && $char === '\\' && $i + 1 < $length) {
                $current .= $char . $value[++$i];
                continue;
            }
            if ($char === '"') {
                $quoted = !$quoted;
            } elseif ($char === $separator && !$quoted) {
                $items[] = $current;
                $current = '';
                continue;
            }
            $current .= $char;
        }
        $items[] = $current;

        return array_values(array_filter(array_map('trim', $items), static fn (string $item): bool => $item !== ''));
    }
}

<?php

declare(strict_types=1);

namespace Core\Http\Body;

/**
 * Builds nested data from form field names with the bracket convention: "a[b][]" (as PHP does),
 * but keeps names as sent: PHP's parse_str() turns "a.b" and "a b" into "a_b", the WHATWG URL
 * standard (application/x-www-form-urlencoded) does not.
 */
final class FormData
{
    private const int MAX_DEPTH = 32;

    private function __construct()
    {
    }

    /**
     * application/x-www-form-urlencoded parser (WHATWG URL §5.1): "&"-separated pairs, "+" is a space.
     *
     * @return array<array-key, mixed>
     */
    public static function parseUrlencoded(string $body): array
    {
        $data = [];
        foreach (explode('&', $body) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            self::add($data, urldecode($name), urldecode($value));
        }

        return $data;
    }

    /**
     * Adds the value under a field name: "a" => $data['a'], "a[b][]" => $data['a']['b'][] .
     *
     * @param array<array-key, mixed> $data
     */
    public static function add(array &$data, string $name, mixed $value): void
    {
        if ($name === '') {
            return;
        }

        $keys = self::keys($name);
        $target = &$data;
        foreach ($keys as $index => $key) {
            $last = $index === count($keys) - 1;
            if ($key === '') {
                if (!is_array($target)) {
                    $target = [];
                }
                if ($last) {
                    $target[] = $value;
                    return;
                }
                $target[] = [];
                $target = &$target[array_key_last($target)];
                continue;
            }
            if (!is_array($target)) {
                $target = [];
            }
            if ($last) {
                $target[$key] = $value;
                return;
            }
            if (!isset($target[$key]) || !is_array($target[$key])) {
                $target[$key] = [];
            }
            $target = &$target[$key];
        }
    }

    /**
     * "a[b][]" => ["a", "b", ""]; a name without valid brackets is one key.
     *
     * @return list<string>
     */
    private static function keys(string $name): array
    {
        $open = strpos($name, '[');
        if ($open === false || $open === 0 || !preg_match('/^(?:\[[^\[\]]*\])+$/', substr($name, $open), $m)) {
            return [$name];
        }

        preg_match_all('/\[([^\[\]]*)\]/', substr($name, $open), $matches);
        $keys = [substr($name, 0, $open), ...$matches[1]];

        return array_slice($keys, 0, self::MAX_DEPTH);
    }
}

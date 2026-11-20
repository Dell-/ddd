<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Proactive negotiation with the Accept header (RFC 9110 §12.5.1).
 *
 * Each available media type gets the quality of the most specific matching media range
 * ("text/html;level=1" > "text/html" > "text/*" > "*\/*"); the highest quality wins,
 * on a tie the server's order of the available types decides. q=0 means "not acceptable".
 */
final readonly class Accept
{
    /**
     * @param list<array{MediaType, float}> $ranges media range and quality, in header order
     */
    private function __construct(private array $ranges)
    {
    }

    /**
     * A missing or empty header accepts everything (RFC 9110 §12.5.1).
     */
    public static function parse(?string $header): self
    {
        $header = trim((string) $header);
        if ($header === '') {
            return new self([[MediaType::fromString('*/*'), 1.0]]);
        }

        $ranges = [];
        foreach (HeaderValue::splitList($header) as $item) {
            $range = MediaType::parse($item);
            if ($range === null) {
                continue; // ignore invalid elements
            }
            $quality = 1.0;
            $parameters = $range->parameters;
            if (isset($parameters['q'])) {
                if (!preg_match('/^(0(\.\d{0,3})?|1(\.0{0,3})?)$/', $parameters['q'])) {
                    continue;
                }
                $quality = (float) $parameters['q'];
                unset($parameters['q']);
                $range = MediaType::fromString($range->essence . self::parametersToString($parameters));
            }
            $ranges[] = [$range, $quality];
        }

        return new self($ranges);
    }

    /**
     * Quality of a concrete media type (0 = not acceptable).
     */
    public function getQuality(MediaType|string $type): float
    {
        $type = is_string($type) ? MediaType::fromString($type) : $type;

        $best = null;
        $bestSpecificity = -1;
        foreach ($this->ranges as [$range, $quality]) {
            if (!$type->matches($range) || !$this->parametersMatch($range, $type)) {
                continue;
            }
            $specificity = ($range->type === '*' ? 0 : 1) + ($range->subtype === '*' ? 0 : 1) + count($range->parameters);
            if ($specificity > $bestSpecificity) {
                $best = $quality;
                $bestSpecificity = $specificity;
            }
        }

        return $best ?? 0.0;
    }

    public function accepts(MediaType|string $type): bool
    {
        return $this->getQuality($type) > 0;
    }

    /**
     * The best of the available media types (in the server's order of preference), or null if none is acceptable.
     *
     * @param list<string> $available
     */
    public function negotiate(array $available): ?string
    {
        $best = null;
        $bestQuality = 0.0;
        foreach ($available as $type) {
            $quality = $this->getQuality($type);
            if ($quality > $bestQuality) {
                $best = $type;
                $bestQuality = $quality;
            }
        }

        return $best;
    }

    /**
     * Range parameters (other than q) must all be present in the type with the same values.
     */
    private function parametersMatch(MediaType $range, MediaType $type): bool
    {
        return array_all(
            $range->parameters,
            static fn (string $value, string $name): bool => strcasecmp((string) $type->parameter($name), $value) === 0
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    private static function parametersToString(array $parameters): string
    {
        $result = '';
        foreach ($parameters as $name => $value) {
            $result .= ';' . $name . '=' . HeaderValue::quote($value);
        }

        return $result;
    }
}

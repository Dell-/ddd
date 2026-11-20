<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Media type or media range (RFC 9110 §8.3.1, §12.5.1): type "/" subtype *( ";" parameter ).
 *
 * Type, subtype and parameter names are case-insensitive and stored in lower case.
 * $suffix is the structured syntax suffix (RFC 6838 §4.2.8): "json" for "application/problem+json".
 *
 *     $type = MediaType::fromString('application/vnd.api+json; charset=UTF-8');
 *     $type->essence;   // "application/vnd.api+json"
 *     $type->suffix;    // "json"
 *     $type->charset;   // "utf-8"
 */
final class MediaType implements \Stringable
{
    /** "type/subtype" without parameters */
    public string $essence {
        get => $this->type . '/' . $this->subtype;
    }

    public ?string $suffix {
        get {
            $position = strrpos($this->subtype, '+');

            return $position === false ? null : substr($this->subtype, $position + 1);
        }
    }

    public ?string $charset {
        get => isset($this->parameters['charset']) ? strtolower($this->parameters['charset']) : null;
    }

    /** "*\/*" or "type/*" */
    public bool $isRange {
        get => $this->type === '*' || $this->subtype === '*';
    }

    /**
     * @param array<string, string> $parameters lower-case names
     */
    private function __construct(
        public private(set) string $type,
        public private(set) string $subtype,
        public private(set) array $parameters = [],
    ) {
    }

    public static function parse(string $value): ?self
    {
        [$essence, $parameters] = HeaderValue::parseParameters($value);
        $parts = explode('/', strtolower($essence), 2);
        if (count($parts) !== 2 || !HeaderValue::isToken($parts[0]) || !HeaderValue::isToken($parts[1])) {
            return null;
        }
        if ($parts[0] === '*' && $parts[1] !== '*') {
            return null;
        }

        return new self($parts[0], $parts[1], $parameters);
    }

    public static function fromString(string $value): self
    {
        return self::parse($value) ?? throw new \InvalidArgumentException(sprintf('Invalid media type "%s".', $value));
    }

    public function parameter(string $name): ?string
    {
        return $this->parameters[strtolower($name)] ?? null;
    }

    public function withParameter(string $name, string $value): self
    {
        return new self($this->type, $this->subtype, [strtolower($name) => $value] + $this->parameters);
    }

    public function withoutParameters(): self
    {
        return new self($this->type, $this->subtype);
    }

    /**
     * Whether this media type is matched by the media range ("*\/*", "text/*", "text/html").
     */
    public function matches(self|string $range): bool
    {
        $range = is_string($range) ? self::fromString($range) : $range;

        return ($range->type === '*' || $range->type === $this->type)
            && ($range->subtype === '*' || $range->subtype === $this->subtype);
    }

    /**
     * JSON (RFC 8259) or a type with the +json suffix (RFC 6839 §3.1).
     */
    public function isJson(): bool
    {
        return $this->essence === 'application/json' || $this->suffix === 'json';
    }

    /**
     * XML (RFC 7303) or a type with the +xml suffix (RFC 7303 §4.2).
     */
    public function isXml(): bool
    {
        return in_array($this->essence, ['application/xml', 'text/xml'], true) || $this->suffix === 'xml';
    }

    #[\Override]
    public function __toString(): string
    {
        $value = $this->essence;
        foreach ($this->parameters as $name => $parameter) {
            $value .= '; ' . $name . '=' . HeaderValue::quote($parameter);
        }

        return $value;
    }
}

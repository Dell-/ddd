<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Content-Disposition (RFC 6266) with UTF-8 file names via filename* (RFC 8187).
 *
 *     ContentDisposition::attachment('звіт.pdf');   // attachment; filename="____.pdf"; filename*=UTF-8''%D0%B7…
 *     ContentDisposition::parse($header)->filename;
 */
final class ContentDisposition implements \Stringable
{
    /** File name without directories and control characters (RFC 6266 §4.3), or null */
    public ?string $filename {
        get => isset($this->parameters['filename']) ? self::sanitizeFilename($this->parameters['filename']) : null;
    }

    /** "name" of a multipart/form-data part (RFC 7578 §4.2) */
    public ?string $name {
        get => $this->parameters['name'] ?? null;
    }

    /**
     * @param array<string, string> $parameters decoded values (filename* is decoded into filename)
     */
    private function __construct(
        public private(set) string $type,
        public private(set) array $parameters = [],
    ) {
    }

    public static function create(DispositionType $type, ?string $filename = null): self
    {
        return new self($type->value, $filename === null ? [] : ['filename' => self::sanitizeFilename($filename)]);
    }

    public static function attachment(?string $filename = null): self
    {
        return self::create(DispositionType::Attachment, $filename);
    }

    public static function inline(?string $filename = null): self
    {
        return self::create(DispositionType::Inline, $filename);
    }

    /**
     * Parses a header value; an ext-value (filename*, RFC 8187) has priority over the plain parameter.
     */
    public static function parse(string $value): ?self
    {
        [$type, $parameters] = HeaderValue::parseParameters($value);
        $type = strtolower($type);
        if (!HeaderValue::isToken($type)) {
            return null;
        }

        $extended = [];
        foreach ($parameters as $name => $parameter) {
            if (str_ends_with($name, '*')) {
                unset($parameters[$name]);
                $decoded = self::decodeExtValue($parameter);
                if ($decoded !== null) {
                    $extended[substr($name, 0, -1)] = $decoded;
                }
            }
        }

        return new self($type, $extended + $parameters);
    }

    public function parameter(string $name): ?string
    {
        return $this->parameters[strtolower($name)] ?? null;
    }

    public function is(DispositionType $type): bool
    {
        return $this->type === $type->value;
    }

    /**
     * filename="ASCII fallback" plus filename*=UTF-8''… for non-ASCII names (RFC 6266 §4.3, Appendix D).
     */
    #[\Override]
    public function __toString(): string
    {
        $value = $this->type;
        foreach ($this->parameters as $name => $parameter) {
            if ($name === 'filename' && preg_match('/[^\x20-\x7E]/', $parameter)) {
                $fallback = (string) preg_replace('/[^\x20-\x7E]/u', '_', $parameter);
                $value .= '; filename=' . HeaderValue::quote($fallback) . "; filename*=UTF-8''" . rawurlencode($parameter);
                continue;
            }
            $value .= '; ' . $name . '=' . HeaderValue::quote($parameter);
        }

        return $value;
    }

    /**
     * File name without directory parts and control characters: recipients must not use path information
     * (RFC 6266 §4.3, RFC 7578 §4.2).
     */
    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $filename);

        return in_array($filename, ['.', '..'], true) ? '' : $filename;
    }

    /**
     * RFC 8187 ext-value: charset "'" [ language ] "'" value-chars (UTF-8 and ISO-8859-1).
     */
    private static function decodeExtValue(string $value): ?string
    {
        if (!preg_match("/^([\\w!#$%&+\\-^`{}~]+)'[\\w-]*'(.*)$/s", $value, $matches)) {
            return null;
        }
        $decoded = rawurldecode($matches[2]);

        return match (strtolower($matches[1])) {
            'utf-8' => mb_check_encoding($decoded, 'UTF-8') ? $decoded : null,
            'iso-8859-1' => mb_convert_encoding($decoded, 'UTF-8', 'ISO-8859-1'),
            default => null,
        };
    }
}

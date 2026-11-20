<?php

declare(strict_types=1);

namespace Core\Http\Body;

use Core\Http\Exception\BadRequestException;
use Core\Http\Header\MediaType;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JSON (RFC 8259): application/json and every "+json" type (RFC 6839), e.g. application/merge-patch+json
 * (RFC 7396), application/json-patch+json (RFC 6902), application/vnd.api+json.
 *
 * Objects become associative arrays. Integers beyond PHP_INT_MAX are kept as strings (RFC 8259 §6).
 * PSR-7 allows only arrays/objects as parsed body, so a top-level scalar is rejected.
 */
final class JsonParser implements BodyParserInterface
{
    public function __construct(private readonly int $depth = 512)
    {
    }

    #[\Override]
    public function supports(MediaType $mediaType): bool
    {
        return $mediaType->isJson();
    }

    #[\Override]
    public function parse(ServerRequestInterface $request, MediaType $mediaType): ParsedBody
    {
        $body = (string) $request->getBody();
        if (trim($body) === '') {
            return new ParsedBody(null);
        }

        $data = self::decode($body, $this->depth, 'JSON body');
        if (!is_array($data)) {
            throw new BadRequestException('The JSON body must be an object or an array.');
        }

        return new ParsedBody($data);
    }

    /**
     * @throws BadRequestException
     */
    public static function decode(string $json, int $depth, string $what): mixed
    {
        // RFC 8259 §8.1: a BOM may be ignored
        if (str_starts_with($json, "\xEF\xBB\xBF")) {
            $json = substr($json, 3);
        }

        try {
            return json_decode($json, true, $depth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            throw new BadRequestException(sprintf('Invalid %s: %s.', $what, $e->getMessage()), $e);
        }
    }
}

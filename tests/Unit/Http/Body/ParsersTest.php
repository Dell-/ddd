<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Body;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Core\Http\Body\BodyParserInterface;
use Core\Http\Body\FormUrlencodedParser;
use Core\Http\Body\JsonLinesParser;
use Core\Http\Body\JsonParser;
use Core\Http\Body\JsonSeqParser;
use Core\Http\Body\XmlParser;
use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\MediaType;
use Tests\Support\Requests;

final class ParsersTest extends Unit
{
    /**
     * @return iterable<string, array{BodyParserInterface, string, bool}>
     */
    public static function supportsProvider(): iterable
    {
        yield 'json' => [new JsonParser(), 'application/json', true];
        yield 'json suffix' => [new JsonParser(), 'application/problem+json; charset=utf-8', true];
        yield 'merge patch' => [new JsonParser(), 'application/merge-patch+json', true];
        yield 'json lines is not json' => [new JsonParser(), 'application/x-ndjson', false];
        foreach (JsonLinesParser::MEDIA_TYPES as $type) {
            yield 'lines ' . $type => [new JsonLinesParser(), $type, true];
        }
        yield 'seq' => [new JsonSeqParser(), 'application/json-seq', true];
        yield 'form' => [new FormUrlencodedParser(), 'application/x-www-form-urlencoded', true];
        yield 'xml' => [new XmlParser(), 'application/xml', true];
        yield 'text xml' => [new XmlParser(), 'text/xml', true];
        yield 'atom' => [new XmlParser(), 'application/atom+xml', true];
        yield 'not xml' => [new XmlParser(), 'text/plain', false];
    }

    #[DataProvider('supportsProvider')]
    public function testSupports(BodyParserInterface $parser, string $type, bool $expected): void
    {
        $this->assertSame($expected, $parser->supports(MediaType::fromString($type)));
    }

    public function testJson(): void
    {
        $this->assertSame(['a' => 1, 'b' => [true, null], 'big' => '123456789012345678901234567890'], $this->parse(new JsonParser(), "\xEF\xBB\xBF" . '{"a":1,"b":[true,null],"big":123456789012345678901234567890}'));
        $this->assertSame([1, 2], $this->parse(new JsonParser(), '[1,2]'));
        $this->assertNull($this->parse(new JsonParser(), "  \n"));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidJsonProvider(): iterable
    {
        yield 'syntax' => ['{"a":', 'Invalid JSON body: Syntax error.'];
        yield 'scalar' => ['"text"', 'The JSON body must be an object or an array.'];
        yield 'number' => ['42', 'The JSON body must be an object or an array.'];
        yield 'depth' => [str_repeat('[', 600) . str_repeat(']', 600), 'Invalid JSON body: Maximum stack depth exceeded.'];
    }

    #[DataProvider('invalidJsonProvider')]
    public function testInvalidJson(string $body, string $message): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage($message);
        $this->parse(new JsonParser(), $body);
    }

    public function testJsonLines(): void
    {
        $body = "{\"id\":1}\r\n\n[2]\n\"three\"\n4\n{\"id\":5}";

        $this->assertSame([['id' => 1], [2], 'three', 4, ['id' => 5]], $this->parse(new JsonLinesParser(), $body, 'application/x-ndjson'));
        $this->assertSame([], $this->parse(new JsonLinesParser(), '', 'application/jsonl'));
    }

    public function testJsonLinesAcrossChunks(): void
    {
        $records = array_map(static fn (int $i): array => ['id' => $i, 'pad' => str_repeat('x', 1000)], range(1, 200));
        $body = implode("\n", array_map(json_encode(...), $records)) . "\n";

        $this->assertCount(200, $this->parse(new JsonLinesParser(), $body, 'application/jsonl'));
    }

    public function testJsonLinesReportsTheLine(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid JSON on line 3: Syntax error.');
        $this->parse(new JsonLinesParser(), "{}\n\n{bad}\n", 'application/x-ndjson');
    }

    public function testJsonLinesLimit(): void
    {
        $this->expectException(ContentTooLargeException::class);
        $this->parse(new JsonLinesParser(maxRecords: 2), "1\n2\n3\n", 'application/x-ndjson');
    }

    public function testJsonSeq(): void
    {
        $body = "\x1E{\"a\":1}\n\x1E[2]\n\x1E\n\x1E\"x\"\n";

        $this->assertSame([['a' => 1], [2], 'x'], $this->parse(new JsonSeqParser(), $body, 'application/json-seq'));
    }

    public function testJsonSeqRequiresRecordSeparators(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('must start with the RS');
        $this->parse(new JsonSeqParser(), "{\"a\":1}\n", 'application/json-seq');
    }

    public function testJsonSeqRejectsTruncatedRecords(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid JSON text 2');
        $this->parse(new JsonSeqParser(), "\x1E{}\n\x1E{\"a\":\n", 'application/json-seq');
    }

    public function testForm(): void
    {
        $this->assertSame(['a' => ['b' => 'c d']], $this->parse(new FormUrlencodedParser(), 'a[b]=c+d', 'application/x-www-form-urlencoded'));
    }

    public function testXml(): void
    {
        $xml = $this->parse(new XmlParser(), '<item id="1"><name>Test</name><![CDATA[x]]></item>', 'application/xml');

        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        $this->assertSame('Test', (string) $xml->name);
        $this->assertSame('1', (string) $xml['id']);
    }

    public function testXmlRejectsDoctype(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('document type declarations are not allowed');
        $this->parse(new XmlParser(), '<?xml version="1.0"?><!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>', 'application/xml');
    }

    public function testInvalidXml(): void
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid XML body');
        $this->parse(new XmlParser(), '<a><b></a>', 'application/xml');
    }

    private function parse(BodyParserInterface $parser, string $body, string $type = 'application/json'): mixed
    {
        $mediaType = MediaType::fromString($type);

        return $parser->parse(Requests::make('POST', '/', ['Content-Type' => $type], $body), $mediaType)->data;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Body;

use Codeception\Test\Unit;
use Core\Http\Body\MultipartParser;
use Core\Http\Body\ParsedBody;
use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ContentTooLargeException;
use Core\Http\Header\MediaType;
use Core\Http\Message\UploadedFile;
use Tests\Support\Requests;

final class MultipartParserTest extends Unit
{
    private const string BOUNDARY = '----WebKitFormBoundary7MA4YWxkTrZu0gW';

    public function testFieldsAndFiles(): void
    {
        $body = $this->body([
            ['Content-Disposition: form-data; name="title"', 'Звіт'],
            ['Content-Disposition: form-data; name="tags[]"', 'a'],
            ['Content-Disposition: form-data; name="tags[]"', 'b'],
            ["Content-Disposition: form-data; name=\"document\"; filename=\"report.txt\"\r\nContent-Type: text/plain", "line 1\r\nline 2\r\n"],
            ["Content-Disposition: form-data; name=\"photos[]\"; filename*=UTF-8''%D1%84.png\r\nContent-Type: image/png", 'PNG'],
            ['Content-Disposition: form-data; name="meta[attachment]"; filename="C:\\Users\\me\\a.bin"', "\0\1\2"],
            ['Content-Disposition: form-data; name="empty"; filename=""', ''],
        ]);

        $parsed = $this->parse($body);

        $this->assertSame(['title' => 'Звіт', 'tags' => ['a', 'b']], $parsed->data);
        $document = $parsed->files['document'];
        $this->assertInstanceOf(UploadedFile::class, $document);
        $this->assertSame(['report.txt', 'text/plain', 16, "line 1\r\nline 2\r\n"], [$document->getClientFilename(), $document->getClientMediaType(), $document->getSize(), $document->getContents()]);
        $this->assertSame('ф.png', $parsed->files['photos'][0]->getClientFilename());
        $this->assertSame('a.bin', $parsed->files['meta']['attachment']->getClientFilename());
        $this->assertSame("\0\1\2", $parsed->files['meta']['attachment']->getContents());
        $this->assertSame(UPLOAD_ERR_NO_FILE, $parsed->files['empty']->getError());
    }

    public function testBoundaryLikeContentAndBigFiles(): void
    {
        // partial delimiters (a delimiter itself must not occur in the content, RFC 2046 §5.1.1)
        $content = 'x' . str_repeat('--' . self::BOUNDARY . ' ', 3) . str_repeat("\r\n--" . substr(self::BOUNDARY, 0, -1) . '!', 5000)
            . random_bytes(300000) . "\r\n--" . substr(self::BOUNDARY, 0, 10);
        $parsed = $this->parse($this->body([['Content-Disposition: form-data; name="f"; filename="big.bin"', $content]]));

        $this->assertSame(strlen($content), $parsed->files['f']->getSize());
        $this->assertSame(hash('sha256', $content), $parsed->files['f']->getHash());
    }

    public function testPreambleEpilogueAndPadding(): void
    {
        $body = "preamble\r\n--" . self::BOUNDARY . "  \r\nContent-Disposition: form-data; name=\"a\"\r\n\r\n1\r\n--" . self::BOUNDARY . "--\r\nepilogue";

        $this->assertSame(['a' => '1'], $this->parse($body)->data);
    }

    public function testEmptyForm(): void
    {
        $this->assertSame([], $this->parse('--' . self::BOUNDARY . "--\r\n")->data);
    }

    /**
     * @return iterable<string, array{string, class-string<\Throwable>, string}>
     */
    public static function malformedProvider(): iterable
    {
        $b = self::BOUNDARY;
        yield 'no closing delimiter' => ["--$b\r\nContent-Disposition: form-data; name=\"a\"\r\n\r\n1", BadRequestException::class, 'closing delimiter'];
        yield 'no name' => ["--$b\r\nContent-Disposition: form-data\r\n\r\n1\r\n--$b--", BadRequestException::class, 'name='];
        yield 'not form-data' => ["--$b\r\nContent-Disposition: attachment; name=\"a\"\r\n\r\n1\r\n--$b--", BadRequestException::class, 'form-data'];
        yield 'bad header' => ["--$b\r\nno colon here\r\n\r\n1\r\n--$b--", BadRequestException::class, 'header'];
        yield 'garbage after delimiter' => ["--{$b}xyz\r\n", BadRequestException::class, 'delimiter'];
        yield 'headers never end' => ["--$b\r\nContent-Disposition: form-data; name=\"a\"", BadRequestException::class, 'part headers'];
    }

    /**
     * @param class-string<\Throwable> $exception
     */
    #[\Codeception\Attribute\DataProvider('malformedProvider')]
    public function testMalformed(string $body, string $exception, string $message): void
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);
        $this->parse($body);
    }

    public function testBoundaryIsRequired(): void
    {
        $this->expectException(BadRequestException::class);
        new MultipartParser()->parse(Requests::make('PUT', '/', [], 'x'), MediaType::fromString('multipart/form-data'));
    }

    public function testLimits(): void
    {
        $tooMany = $this->body(array_fill(0, 3, ['Content-Disposition: form-data; name="a[]"', '1']));
        try {
            $this->parse($tooMany, new MultipartParser(maxParts: 2));
            $this->fail('too many parts');
        } catch (ContentTooLargeException $e) {
            $this->assertStringContainsString('parts', $e->getMessage());
        }

        $this->expectException(ContentTooLargeException::class);
        $this->parse($this->body([['Content-Disposition: form-data; name="a"', str_repeat('x', 11)]]), new MultipartParser(maxFieldSize: 10));
    }

    /**
     * @param list<array{string, string}> $parts headers and content
     */
    private function body(array $parts): string
    {
        $body = '';
        foreach ($parts as [$headers, $content]) {
            $body .= '--' . self::BOUNDARY . "\r\n" . $headers . "\r\n\r\n" . $content . "\r\n";
        }

        return $body . '--' . self::BOUNDARY . "--\r\n";
    }

    private function parse(string $body, MultipartParser $parser = new MultipartParser()): ParsedBody
    {
        $type = 'multipart/form-data; boundary=' . self::BOUNDARY;

        return $parser->parse(Requests::make('PUT', '/', ['Content-Type' => $type], $body), MediaType::fromString($type));
    }
}

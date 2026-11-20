<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Response;

use Codeception\Test\Unit;
use Core\Http\Exception\PreconditionFailedException;
use Core\Http\Exception\RangeNotSatisfiableException;
use Core\Http\Header\DispositionType;
use Core\Http\Response\FileResponse;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Requests;
use Tests\Support\TempDirectoryTrait;

final class FileResponseTest extends Unit
{
    use TempDirectoryTrait;

    private string $path;

    private FileResponse $response;

    protected function _before(): void
    {
        $this->path = $this->createTempDirectory(['data.txt' => '0123456789']) . '/data.txt';
        touch($this->path, 1700000000);
        clearstatcache();
        $this->response = new FileResponse($this->path, 'Дані.txt');
    }

    protected function _after(): void
    {
        $this->removeTempDirectory();
    }

    public function testHeaders(): void
    {
        $this->assertSame(200, $this->response->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $this->response->getHeaderLine('Content-Type'));
        $this->assertSame('10', $this->response->getHeaderLine('Content-Length'));
        $this->assertSame("attachment; filename=____.txt; filename*=UTF-8''%D0%94%D0%B0%D0%BD%D1%96.txt", $this->response->getHeaderLine('Content-Disposition'));
        $this->assertSame('Tue, 14 Nov 2023 22:13:20 GMT', $this->response->getHeaderLine('Last-Modified'));
        $this->assertMatchesRegularExpression('/^"[0-9a-f]{32}"$/', $this->response->getHeaderLine('ETag'));
        $this->assertSame('bytes', $this->response->getHeaderLine('Accept-Ranges'));
        $this->assertSame('0123456789', (string) $this->response->getBody());
        $this->assertStringStartsWith('inline', new FileResponse($this->path, disposition: DispositionType::Inline)->getHeaderLine('Content-Disposition'));
        $this->assertSame('application/x-custom', new FileResponse($this->path, mediaType: 'application/x-custom')->getHeaderLine('Content-Type'));
    }

    public function testMissingFile(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FileResponse('/does/not/exist');
    }

    public function testNotModified(): void
    {
        $etag = $this->response->getHeaderLine('ETag');

        foreach ([
            ['If-None-Match' => $etag],
            ['If-None-Match' => 'W/' . $etag . ', "other"'],
            ['If-None-Match' => '*'],
            ['If-Modified-Since' => 'Tue, 14 Nov 2023 22:13:20 GMT'],
            ['If-Modified-Since' => 'Tuesday, 14-Nov-23 22:13:20 GMT'],
            ['If-Modified-Since' => 'Tue Nov 14 22:13:20 2023'],
        ] as $headers) {
            $response = $this->prepare('GET', $headers);
            $this->assertSame(304, $response->getStatusCode(), json_encode($headers));
            $this->assertSame($etag, $response->getHeaderLine('ETag'));
            $this->assertSame('', (string) $response->getBody());
        }
    }

    public function testModified(): void
    {
        $this->assertSame(200, $this->prepare('GET', ['If-None-Match' => '"other"'])->getStatusCode());
        $this->assertSame(200, $this->prepare('GET', ['If-Modified-Since' => 'Mon, 13 Nov 2023 00:00:00 GMT'])->getStatusCode());
        $this->assertSame(200, $this->prepare('GET', ['If-Modified-Since' => 'invalid date'])->getStatusCode());
        // If-None-Match takes precedence over If-Modified-Since (RFC 9110 §13.1.3)
        $this->assertSame(200, $this->prepare('GET', ['If-None-Match' => '"other"', 'If-Modified-Since' => 'Tue, 14 Nov 2030 00:00:00 GMT'])->getStatusCode());
    }

    public function testPreconditionFailed(): void
    {
        $this->assertSame(200, $this->prepare('GET', ['If-Match' => $this->response->getHeaderLine('ETag')])->getStatusCode());
        foreach ([
            ['If-Match' => '"other"'],
            ['If-Match' => 'W/' . $this->response->getHeaderLine('ETag')],
            ['If-Unmodified-Since' => 'Mon, 13 Nov 2023 00:00:00 GMT'],
        ] as $headers) {
            try {
                $this->prepare('GET', $headers);
                $this->fail('412 expected for ' . json_encode($headers));
            } catch (PreconditionFailedException) {
                $this->assertTrue(true);
            }
        }
        $this->expectException(PreconditionFailedException::class);
        $this->prepare('PUT', ['If-None-Match' => '*']);
    }

    public function testRanges(): void
    {
        foreach ([
            'bytes=0-3' => ['0123', 'bytes 0-3/10'],
            'bytes=7-' => ['789', 'bytes 7-9/10'],
            'bytes=-2' => ['89', 'bytes 8-9/10'],
            'bytes=5-100' => ['56789', 'bytes 5-9/10'],
            'bytes=-100' => ['0123456789', 'bytes 0-9/10'],
        ] as $range => [$body, $contentRange]) {
            $response = $this->prepare('GET', ['Range' => $range]);
            $this->assertSame(206, $response->getStatusCode(), $range);
            $this->assertSame($body, (string) $response->getBody(), $range);
            $this->assertSame($contentRange, $response->getHeaderLine('Content-Range'));
            $this->assertSame((string) strlen($body), $response->getHeaderLine('Content-Length'));
        }

        $this->assertSame(200, $this->prepare('GET', ['Range' => 'bytes=0-1,4-5'])->getStatusCode(), 'multiple ranges are ignored');
        $this->assertSame(200, $this->prepare('GET', ['Range' => 'items=0-1'])->getStatusCode(), 'unknown unit');
        $this->assertSame(200, $this->prepare('HEAD', ['Range' => 'bytes=0-1'])->getStatusCode(), 'only GET');
    }

    public function testIfRange(): void
    {
        $etag = $this->response->getHeaderLine('ETag');

        $this->assertSame(206, $this->prepare('GET', ['Range' => 'bytes=0-1', 'If-Range' => $etag])->getStatusCode());
        $this->assertSame(206, $this->prepare('GET', ['Range' => 'bytes=0-1', 'If-Range' => 'Tue, 14 Nov 2023 22:13:20 GMT'])->getStatusCode());
        $this->assertSame(200, $this->prepare('GET', ['Range' => 'bytes=0-1', 'If-Range' => '"changed"'])->getStatusCode());
    }

    public function testUnsatisfiableRange(): void
    {
        try {
            $this->prepare('GET', ['Range' => 'bytes=10-']);
            $this->fail('416 expected');
        } catch (RangeNotSatisfiableException $e) {
            $this->assertSame(['Content-Range' => ['bytes */10']], $e->getHeaders());
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function prepare(string $method, array $headers): ResponseInterface
    {
        return $this->response->prepare(Requests::make($method, '/file', $headers));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\ServerRequest;
use Core\Http\Message\ServerRequestFactory;
use Core\Http\Message\Stream;
use Core\Http\Message\UploadedFile;
use Core\Http\Method;
use Tests\Support\Requests;

final class ServerRequestTest extends Unit
{
    public function testFromGlobalsArrays(): void
    {
        $request = ServerRequestFactory::fromArrays(
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/form?x=1',
                'SERVER_PROTOCOL' => 'HTTP/2',
                'HTTP_HOST' => 'example.com:8080',
                'HTTPS' => 'on',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'CONTENT_LENGTH' => '7',
                'HTTP_X_REQUEST_ID' => '42',
                'PHP_AUTH_USER' => 'u',
                'PHP_AUTH_PW' => 'p',
            ],
            ['x' => '1'],
            ['name' => 'value'],
            ['session' => 'abc'],
            [],
            Stream::create('name=value')
        );

        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https://example.com:8080/form?x=1', (string) $request->getUri());
        $this->assertSame('2', $request->getProtocolVersion());
        $this->assertSame('42', $request->getHeaderLine('X-Request-Id'));
        $this->assertSame('Basic ' . base64_encode('u:p'), $request->getHeaderLine('Authorization'));
        $this->assertSame(['x' => '1'], $request->getQueryParams());
        $this->assertSame(['name' => 'value'], $request->getParsedBody());
        $this->assertSame('abc', $request->getCookieParam('session'));
        $this->assertSame(7, $request->getContentLength());
        $this->assertTrue($request->isSecure());
        $this->assertSame('https://example.com:8080', $request->getOrigin());
        $this->assertSame('POST', $request->getServerParams()['REQUEST_METHOD']);
    }

    public function testParsedBodyOnlyForWhatPhpParses(): void
    {
        $json = ServerRequestFactory::fromArrays(['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'], [], ['ignored' => '1']);
        $put = ServerRequestFactory::fromArrays(['REQUEST_METHOD' => 'PUT', 'CONTENT_TYPE' => 'application/x-www-form-urlencoded'], [], ['ignored' => '1']);

        $this->assertNull($json->getParsedBody());
        $this->assertNull($put->getParsedBody());
    }

    public function testAbsoluteFormAndDefaults(): void
    {
        $request = ServerRequestFactory::fromArrays(['REQUEST_URI' => 'http://proxy.example/a?b=1', 'SERVER_NAME' => 'srv', 'SERVER_PORT' => '8000']);

        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('http://srv:8000/a?b=1', (string) $request->getUri());
        $this->assertSame('http://localhost/', (string) ServerRequestFactory::fromArrays([])->getUri());
    }

    public function testFilesFromPhpArrays(): void
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($tmp, 'abc');
        $request = ServerRequestFactory::fromArrays(['REQUEST_METHOD' => 'POST'], files: [
            'avatar' => ['name' => 'me.png', 'type' => 'image/png', 'tmp_name' => $tmp, 'error' => UPLOAD_ERR_OK, 'size' => 3],
            'photos' => [
                'name' => ['a.jpg', ''],
                'type' => ['image/jpeg', ''],
                'tmp_name' => [$tmp, ''],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
                'size' => [3, 0],
            ],
            'docs' => ['name' => ['passport' => 'p.pdf'], 'type' => ['passport' => 'application/pdf'], 'tmp_name' => ['passport' => $tmp], 'error' => ['passport' => 0], 'size' => ['passport' => 3]],
        ]);

        $this->assertSame('me.png', $request->getUploadedFile('avatar')?->getClientFilename());
        $this->assertSame('abc', $request->getUploadedFile('avatar')?->getContents());
        $this->assertSame('a.jpg', $request->getUploadedFile('photos.0')?->getClientFilename());
        $this->assertSame('a.jpg', $request->getUploadedFile('photos[0]')?->getClientFilename());
        $this->assertSame(UPLOAD_ERR_NO_FILE, $request->getUploadedFile('photos.1')?->getError());
        $this->assertCount(2, $request->getUploadedFileList('photos'));
        $this->assertCount(1, $request->getUploadedFileList('avatar'));
        $this->assertSame([], $request->getUploadedFileList('missing'));
        $this->assertSame('p.pdf', $request->getUploadedFile('docs[passport]')?->getClientFilename());
        $this->assertNull($request->getUploadedFile('photos'), 'a list is not a file');
        unlink($tmp);
    }

    public function testInputHelpers(): void
    {
        $request = Requests::make('POST', '/?a=query&f[s]=nested&id=query')
            ->withParsedBody(['a' => 'body', 'deep' => ['x' => ['y' => 1]]])
            ->withAttribute(ServerRequest::ATTRIBUTE_ROUTE, 'item')
            ->withAttribute(ServerRequest::ATTRIBUTE_ROUTE_PARAMS, ['id' => '7']);

        $this->assertSame('nested', $request->getQueryParam('f.s'));
        $this->assertSame('nested', $request->getQueryParam('f[s]'));
        $this->assertSame(1, $request->getBodyParam('deep.x.y'));
        $this->assertSame('d', $request->getBodyParam('missing', 'd'));
        $this->assertSame('7', $request->input('id'), 'route parameter first');
        $this->assertSame('body', $request->input('a'), 'then body');
        $this->assertSame('nested', $request->input('f.s'), 'then query');
        $this->assertSame('item', $request->getRouteName());
        $this->assertSame(['id' => '7'], $request->getRouteParams());
        $this->assertSame('v', $request->withParsedBody((object) ['k' => 'v'])->getBodyParam('k'));
    }

    public function testMethodAndNegotiationHelpers(): void
    {
        $request = Requests::make('patch', '/', ['Accept' => 'application/json;q=0.9, */*;q=0.1', 'Content-Type' => 'application/merge-patch+json']);

        $this->assertNull($request->getMethodEnum(), 'lower-case is not a standard method');
        $this->assertSame(Method::Patch, Requests::make('PATCH')->getMethodEnum());
        $this->assertTrue(Requests::make('PATCH')->isMethod(Method::Patch));
        $this->assertTrue(Requests::make('PATCH')->isMethod('patch'));
        $this->assertTrue($request->accepts('application/json'));
        $this->assertTrue($request->getMediaType()?->isJson());
        $this->assertSame('application/json', $request->getAccept()->negotiate(['text/html', 'application/json']));
    }

    public function testBodyAsFile(): void
    {
        $request = Requests::make('PUT', '/files/x', ['Content-Type' => 'image/png', 'Content-Disposition' => "attachment; filename*=UTF-8''%D1%84%D0%BE%D1%82%D0%BE.png"], 'PNGDATA');
        $file = $request->getBodyAsFile();

        $this->assertSame('фото.png', $file->getClientFilename());
        $this->assertSame('image/png', $file->getClientMediaType());
        $this->assertSame(7, $file->getSize());
        $this->assertSame('PNGDATA', $file->getContents());
        $this->assertSame('default.bin', Requests::make('PUT', '/', [], 'x')->getBodyAsFile('default.bin')->getClientFilename());
    }

    public function testPsrImmutability(): void
    {
        $request = Requests::make();
        $changed = $request->withAttribute('a', 1)->withQueryParams(['q' => 1])->withCookieParams(['c' => '1'])->withParsedBody(['b' => 1]);

        $this->assertSame([], $request->getAttributes());
        $this->assertSame(1, $changed->getAttribute('a'));
        $this->assertNull($changed->withoutAttribute('a')->getAttribute('a'));
        $this->assertNull($changed->getAttribute('missing'));
    }

    public function testValidation(): void
    {
        foreach ([fn () => Requests::make()->withParsedBody('string'), fn () => Requests::make()->withUploadedFiles(['a' => 'not a file'])] as $call) {
            try {
                $call();
                $this->fail('must be rejected');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function testFromPsr(): void
    {
        $request = Requests::make()->withAttribute('a', 1);

        $this->assertSame($request, ServerRequest::fromPsr($request));
        $foreign = new \GuzzleHttp\Psr7\ServerRequest('POST', 'https://x.example/p?q=1', ['X-A' => 'b'], 'body')
            ->withAttribute('a', 1)
            ->withQueryParams(['q' => '1'])
            ->withUploadedFiles(['f' => new UploadedFile(Stream::create('x'), 1)]);
        $converted = ServerRequest::fromPsr($foreign);

        $this->assertSame(['POST', 'https://x.example/p?q=1', 'b', 1, ['q' => '1'], 'body'], [
            $converted->getMethod(), (string) $converted->getUri(), $converted->getHeaderLine('X-A'),
            $converted->getAttribute('a'), $converted->getQueryParams(), (string) $converted->getBody(),
        ]);
        $this->assertSame('x', $converted->getUploadedFile('f')?->getContents());
    }
}

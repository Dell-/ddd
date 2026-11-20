<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

/**
 * Uploads (multipart POST and PUT, raw body, data URI in JSON) and downloads (Content-Disposition, ranges, caching).
 */
final class FilesCest
{
    public function multipartPost(ApiTester $I): void
    {
        $I->sendPost('/upload', ['title' => 'Звіт'], [
            'document' => codecept_data_dir('files/hello.txt'),
            'photos' => [codecept_data_dir('files/hello.txt'), codecept_data_dir('files/hello.txt')],
        ]);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'method' => 'POST',
            'title' => 'Звіт',
            'files' => [
                'document' => [['ok' => true, 'name' => 'hello.txt', 'type' => 'text/plain', 'content' => "Hello, file!\n0123456789\n"]],
            ],
        ]);
        $files = json_decode($I->grabResponse(), true)['files'];
        $I->assertCount(2, $files['photos']);
    }

    public function multipartPutIsParsedByTheFramework(ApiTester $I): void
    {
        $boundary = 'X-BOUNDARY-' . bin2hex(random_bytes(4));
        $body = "--$boundary\r\nContent-Disposition: form-data; name=\"title\"\r\n\r\nPUT upload\r\n"
            . "--$boundary\r\nContent-Disposition: form-data; name=\"meta[attachment]\"; filename*=UTF-8''%D1%84%D0%B0%D0%B9%D0%BB.txt\r\nContent-Type: text/plain\r\n\r\nUTF-8 name\r\n"
            . "--$boundary--\r\n";

        $I->haveHttpHeader('Content-Type', "multipart/form-data; boundary=$boundary");
        $I->sendPut('/upload', $body);

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'method' => 'PUT',
            'title' => 'PUT upload',
            'files' => ['meta.attachment' => [['ok' => true, 'name' => 'файл.txt', 'clientType' => 'text/plain', 'content' => 'UTF-8 name']]],
        ]);
    }

    public function rawBodyUpload(ApiTester $I): void
    {
        $png = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4DwQACfsD/Z8fLAAAAAAASUVORK5CYII=');

        $I->haveHttpHeader('Content-Type', 'image/png');
        $I->haveHttpHeader('Content-Disposition', "attachment; filename*=UTF-8''%D1%84%D0%BE%D1%82%D0%BE.png");
        $I->sendPut('/files/pixel', $png);

        $I->seeResponseCodeIs(201);
        $I->seeHttpHeader('Location', '/files/pixel');
        $I->seeResponseContainsJson([
            'name' => 'фото.png',
            'clientType' => 'image/png',
            'type' => 'image/png',
            'size' => strlen($png),
            'sha256' => hash('sha256', $png),
            'storedAs' => 'png',
        ]);
    }

    public function dataUriInJson(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/upload', json_encode(['title' => 'Avatar', 'avatar' => 'data:text/plain;base64,' . base64_encode('pixels')]));

        $I->seeResponseContainsJson(['title' => 'Avatar', 'files' => ['avatar' => [['ok' => true, 'clientType' => 'text/plain', 'content' => 'pixels']]]]);
    }

    public function tooLargeBody(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/octet-stream');
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendPut('/files/big', str_repeat('x', 1048577));

        $I->seeResponseCodeIs(413);
        $I->seeResponseContainsJson(['title' => 'Content Too Large']);
    }

    public function download(ApiTester $I): void
    {
        $I->sendGet('/download/hello.txt');

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'text/plain; charset=utf-8');
        $I->seeHttpHeader('Content-Disposition', "attachment; filename=\"______ hello.txt\"; filename*=UTF-8''%D0%9F%D1%80%D0%B8%D0%B2%D1%96%D1%82%20hello.txt");
        $I->seeHttpHeader('Accept-Ranges', 'bytes');
        $I->seeResponseEquals("Hello, file!\n0123456789\n");

        $I->sendGet('/download/hello.txt?inline=1');
        $I->assertStringStartsWith('inline;', (string) $I->grabHttpHeader('Content-Disposition'));
    }

    public function rangeAndConditionalRequests(ApiTester $I): void
    {
        $I->haveHttpHeader('Range', 'bytes=0-4');
        $I->sendGet('/download/hello.txt');
        $I->seeResponseCodeIs(206);
        $I->seeHttpHeader('Content-Range', 'bytes 0-4/24');
        $I->seeResponseEquals('Hello');
        $etag = (string) $I->grabHttpHeader('ETag');

        $I->deleteHeader('Range');
        $I->haveHttpHeader('If-None-Match', $etag);
        $I->sendGet('/download/hello.txt');
        $I->seeResponseCodeIs(304);
        $I->seeResponseEquals('');

        $I->deleteHeader('If-None-Match');
        $I->haveHttpHeader('Range', 'bytes=100-');
        $I->sendGet('/download/hello.txt');
        $I->seeResponseCodeIs(416);
        $I->seeHttpHeader('Content-Range', 'bytes */24');
    }
}

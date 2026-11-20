<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\HttpFactory;
use Core\Http\Message\ServerRequest;

final class HttpFactoryTest extends Unit
{
    public function testPsr17(): void
    {
        $factory = new HttpFactory();

        $this->assertSame('POST', $factory->createRequest('POST', 'https://x.example/a')->getMethod());
        $this->assertSame([404, 'Not Found'], [$factory->createResponse(404)->getStatusCode(), $factory->createResponse(404)->getReasonPhrase()]);
        $this->assertSame('Custom', $factory->createResponse(404, 'Custom')->getReasonPhrase());
        $this->assertInstanceOf(ServerRequest::class, $factory->createServerRequest('GET', '/', ['A' => 1]));
        $this->assertSame(['A' => 1], $factory->createServerRequest('GET', '/', ['A' => 1])->getServerParams());
        $this->assertSame('abc', (string) $factory->createStream('abc'));
        $this->assertSame('x', (string) $factory->createStreamFromResource((function () { $r = fopen('php://memory', 'r+'); fwrite($r, 'x'); rewind($r); return $r; })()));
        $this->assertStringStartsWith('Hello', (string) $factory->createStreamFromFile(codecept_data_dir('files/hello.txt')));
        $this->assertSame(3, $factory->createUploadedFile($factory->createStream('abc'))->getSize());
        $this->assertSame('/p?q', (string) $factory->createUri('/p?q'));
    }

    public function testInvalidStreamMode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpFactory()->createStreamFromFile(codecept_data_dir('files/hello.txt'), 'z');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\Stream;

final class StreamTest extends Unit
{
    public function testReadWriteSeek(): void
    {
        $stream = Stream::create('hello');

        $this->assertSame(5, $stream->getSize());
        $this->assertSame('he', $stream->read(2));
        $this->assertSame(2, $stream->tell());
        $this->assertSame('llo', $stream->getContents());
        $this->assertTrue($stream->eof() || $stream->read(1) === '');
        $stream->write(' world');
        $this->assertSame('hello world', (string) $stream);
        $this->assertSame(11, $stream->getSize());
        $stream->seek(-5, SEEK_END);
        $this->assertSame('world', $stream->read(10));
        $this->assertTrue($stream->isReadable() && $stream->isWritable() && $stream->isSeekable());
        $this->assertSame('php://temp', $stream->getMetadata('uri'));
    }

    public function testReadOnlyFile(): void
    {
        $stream = Stream::fromFile(codecept_data_dir('files/hello.txt'));

        $this->assertTrue($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertSame((int) filesize(codecept_data_dir('files/hello.txt')), $stream->getSize());
        $this->expectException(\RuntimeException::class);
        $stream->write('x');
    }

    public function testDetach(): void
    {
        $stream = Stream::create('x');
        $resource = $stream->detach();

        $this->assertIsResource($resource);
        $this->assertNull($stream->getSize());
        $this->assertSame('', (string) $stream);
        $this->assertSame([], $stream->getMetadata());
        $this->assertFalse($stream->isReadable());
        $this->expectException(\RuntimeException::class);
        $stream->tell();
    }

    public function testInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream('not a resource');
    }

    public function testMissingFile(): void
    {
        $this->expectException(\RuntimeException::class);
        Stream::fromFile('/does/not/exist');
    }
}

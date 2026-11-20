<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Message;

use Codeception\Test\Unit;
use Core\Http\Message\Stream;
use Core\Http\Message\UploadedFile;
use Tests\Support\TempDirectoryTrait;

final class UploadedFileTest extends Unit
{
    use TempDirectoryTrait;

    private const string PNG = "\x89PNG\r\n\x1a\n\0\0\0\rIHDR\0\0\0\x01\0\0\0\x01\x08\x06\0\0\0\x1f\x15\xc4\x89\0\0\0\rIDATx\x9cc\xf8\x0f\0\0\x01\x01\0\x05\x18\xd8N\0\0\0\0IEND\xaeB`\x82";

    protected function _after(): void
    {
        $this->removeTempDirectory();
    }

    public function testFriendlyApi(): void
    {
        $file = UploadedFile::fromString(self::PNG, '../../Фото.PNG', 'text/plain');

        $this->assertTrue($file->isOk());
        $this->assertSame('The file was uploaded.', $file->getErrorMessage());
        $this->assertSame('../../Фото.PNG', $file->getClientFilename(), 'raw client value');
        $this->assertSame('Фото.PNG', $file->getSafeClientFilename());
        $this->assertSame('png', $file->getClientExtension());
        $this->assertSame('text/plain', $file->getClientMediaType(), 'what the client claims');
        $this->assertSame('image/png', $file->getMediaType(), 'what the content is');
        $this->assertTrue($file->hasMediaType('image/*'));
        $this->assertFalse($file->hasMediaType('image/jpeg', 'application/pdf'));
        $this->assertSame(strlen(self::PNG), $file->getSize());
        $this->assertSame(hash('sha256', self::PNG), $file->getHash());
        $this->assertSame(self::PNG, $file->getContents());
    }

    public function testStoreWithASafeRandomName(): void
    {
        $directory = $this->createTempDirectory() . '/nested/dir';
        $file = UploadedFile::fromString('content', '../evil.php.txt');

        $path = $file->store($directory);

        $this->assertMatchesRegularExpression('#/nested/dir/[0-9a-f]{32}\.txt$#', $path);
        $this->assertSame('content', file_get_contents($path));
        $this->assertTrue($file->isMoved());
        $this->assertSame('text/plain', $file->getMediaType(), 'metadata stays available after the move');
        unlink($path);
    }

    public function testStoreWithAName(): void
    {
        $directory = $this->createTempDirectory();
        $path = UploadedFile::fromString('x')->store($directory, '../name.txt');

        $this->assertSame($directory . '/name.txt', $path);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');
        UploadedFile::fromString('y')->store($directory, 'name.txt');
    }

    public function testMoveFileOnce(): void
    {
        $directory = $this->createTempDirectory(['upload.tmp' => 'data']);
        $file = new UploadedFile($directory . '/upload.tmp', 4, UPLOAD_ERR_OK, 'a.txt', 'text/plain');

        $file->moveTo($directory . '/moved.txt');

        $this->assertFileExists($directory . '/moved.txt');
        $this->assertFileDoesNotExist($directory . '/upload.tmp');
        $this->expectException(\RuntimeException::class);
        $file->getStream();
    }

    public function testFailedUpload(): void
    {
        $file = new UploadedFile(Stream::create(), 0, UPLOAD_ERR_INI_SIZE, 'big.iso');

        $this->assertFalse($file->isOk());
        $this->assertSame('The file exceeds the upload_max_filesize limit.', $file->getErrorMessage());
        $this->expectException(\RuntimeException::class);
        $file->getContents();
    }

    public function testInvalidErrorCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile(Stream::create(), 0, 99);
    }

    public function testDataUri(): void
    {
        $file = UploadedFile::fromDataUri('data:image/png;base64,' . base64_encode(self::PNG), 'pixel.png');

        $this->assertSame('image/png', $file->getClientMediaType());
        $this->assertSame('image/png', $file->getMediaType());
        $this->assertSame('pixel.png', $file->getClientFilename());
        $this->assertSame('Hello, world', UploadedFile::fromDataUri('data:,Hello%2C%20world')->getContents());
        $this->assertSame('text/plain;charset=US-ASCII', UploadedFile::fromDataUri('data:,x')->getClientMediaType());
    }

    public function testBase64(): void
    {
        $this->assertSame("\xfb\xff", UploadedFile::fromBase64('-_8')->getContents(), 'URL-safe alphabet');
        $this->assertSame('abc', UploadedFile::fromBase64(base64_encode('abc'), 'a.txt', 'text/plain')->getContents());
    }

    public function testInvalidDataUri(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UploadedFile::fromDataUri('data:image/png;base64,***');
    }
}

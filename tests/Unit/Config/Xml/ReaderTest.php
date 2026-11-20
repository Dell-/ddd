<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Xml;

use Codeception\Test\Unit;
use Core\Config\Xml\Reader;
use Core\Filesystem\Content\Xml\Dom\MergeRules;
use Core\Filesystem\Content\Xml\Dom\MergerFactory;
use Core\Filesystem\Directory\Factory as DirectoryFactory;
use Core\Filesystem\File\Reader as FileReader;
use Core\Filesystem\IteratorFactory;
use Tests\Support\TempDirectoryTrait;

final class ReaderTest extends Unit
{
    use TempDirectoryTrait;

    protected function _after(): void
    {
        $this->removeTempDirectory();
    }

    public function testFilesAreMergedFromLessToMoreSpecific(): void
    {
        $directory = $this->createTempDirectory([
            'b.local.cfg.xml' => '<cfg><item name="x">b.local</item><item name="b">1</item></cfg>',
            'local.cfg.xml' => '<cfg><item name="x">local</item></cfg>',
            'cfg.xml' => '<cfg><item name="x">base</item><item name="b">0</item></cfg>',
            'a.cfg.xml' => '<cfg><item name="a">1</item></cfg>',
        ]);

        $xPath = new \DOMXPath($this->createReader($directory)->read());

        // cfg.xml < a.cfg.xml < local.cfg.xml < b.local.cfg.xml
        $this->assertSame('b.local', $xPath->evaluate('string(/cfg/item[@name="x"])'));
        $this->assertSame('1', $xPath->evaluate('string(/cfg/item[@name="a"])'));
        $this->assertSame('1', $xPath->evaluate('string(/cfg/item[@name="b"])'));
    }

    public function testOnlyMatchingFilesAreRead(): void
    {
        $directory = $this->createTempDirectory([
            'cfg.xml' => '<cfg><item name="x">1</item></cfg>',
            'other.xml' => 'not even xml',
            'cfg.xml.dist' => 'not even xml',
        ]);

        $document = $this->createReader($directory)->read();

        $this->assertSame('1', (new \DOMXPath($document))->evaluate('string(/cfg/item)'));
    }

    public function testNoMatchingFilesIsAnError(): void
    {
        $directory = $this->createTempDirectory(['other.xml' => '<cfg/>']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No configuration files matching');
        $this->createReader($directory)->read();
    }

    public function testMissingDirectoryIsAnError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->createReader('/does/not/exist')->read();
    }

    public function testInvalidFileIsReportedWithItsName(): void
    {
        $directory = $this->createTempDirectory([
            'routes.xml' => '<routes><route method="GET" name="/" action="A"/></routes>',
            'bad.routes.xml' => '<routes><route method="FOO" name="/" action="A"/></routes>',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('#Invalid configuration file ".*bad\.routes\.xml".*FOO#');
        $this->createReader($directory, '#routes\.xml$#', BP . '/Core/Route/etc/routes.xsd')->read();
    }

    private function createReader(string $directory, string $pattern = '#cfg\.xml$#', ?string $schema = null): Reader
    {
        return new Reader(
            $directory,
            new DirectoryFactory(),
            new IteratorFactory(),
            new FileReader(),
            new MergerFactory(),
            $pattern,
            $schema,
            new MergeRules()
        );
    }
}

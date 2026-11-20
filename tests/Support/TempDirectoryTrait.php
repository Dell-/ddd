<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Creates a temporary directory with files for a test and removes it afterwards.
 */
trait TempDirectoryTrait
{
    private ?string $tempDirectory = null;

    /**
     * @param array<string, string> $files file name => content
     */
    protected function createTempDirectory(array $files = []): string
    {
        $this->tempDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ddd-test-', true);
        mkdir($this->tempDirectory);
        foreach ($files as $name => $content) {
            file_put_contents($this->tempDirectory . DIRECTORY_SEPARATOR . $name, $content);
        }

        return $this->tempDirectory;
    }

    /**
     * Call it from _after() of the test.
     */
    protected function removeTempDirectory(): void
    {
        if ($this->tempDirectory === null) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tempDirectory);
        $this->tempDirectory = null;
    }
}

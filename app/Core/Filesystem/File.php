<?php
namespace Core\Filesystem;

use Core\Helper\FilesystemHelper;

/**
 * Class File
 */
class File
{
    /**
     * Read data
     *
     * @param array $path
     * @param IteratorFactoryInterface $iteratorFactory
     * @return array
     * @throws \Exception
     */
    public function read(array $path, IteratorFactoryInterface $iteratorFactory)
    {
        $result = [];
        foreach ($path as $item) {
            $directory = new \RecursiveDirectoryIterator(FilesystemHelper::normalizePath($item));
            $directory->setFlags(\RecursiveDirectoryIterator::SKIP_DOTS);
            $filesPath = array_keys(
                iterator_to_array(
                    $iteratorFactory->create(new \RecursiveIteratorIterator($directory))
                )
            );
            foreach ($filesPath as $filePath) {
                if (!is_readable($filePath)) {
                    throw new \Exception("The file '{$filePath}' cannot be read.");
                }
                $result[$filePath] = file_get_contents($filePath);
            }
        }

        return $result;
    }
}

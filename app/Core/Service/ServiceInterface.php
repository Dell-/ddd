<?php
namespace Core\Service;

/**
 * Interface ServiceInterface
 */
interface ServiceInterface
{
    /**
     * @return string
     */
    public function getVersion();

    /**
     * Run service
     */
    public function run();
}

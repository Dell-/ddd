<?php
namespace Core\Request;

use Core\Response\ResponseInterface;

/**
 * Interface HandlerInterface
 */
interface HandlerInterface
{
    /**
     * @return ResponseInterface
     */
    public function handle(RequestInterface $request);
}

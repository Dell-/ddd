<?php

declare(strict_types=1);

namespace Core\Request;

interface HandlerInterface
{
    /**
     * Handles the request and returns the output.
     *
     * @throws RequestException
     */
    public function handle(RequestInterface $request): string;
}

<?php

declare(strict_types=1);

namespace Core\Route;

interface UrlGeneratorInterface
{
    /**
     * URL (path and query string) of the named route; parameters go where the route declares them.
     *
     * @param array<string, mixed> $parameters
     * @throws \InvalidArgumentException unknown route, missing, undeclared or invalid parameter
     */
    public function generate(string $name, array $parameters = []): string;
}

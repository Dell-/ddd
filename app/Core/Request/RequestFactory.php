<?php

declare(strict_types=1);

namespace Core\Request;

/**
 * Creates the request of the current process (HTTP or console).
 */
class RequestFactory
{
    public function __construct(
        private readonly Http\Parser $httpParser,
        private readonly Cli\Parser $cliParser,
    ) {
    }

    public function create(): RequestInterface
    {
        return PHP_SAPI === 'cli' ? $this->createCliRequest() : $this->createHttpRequest();
    }

    private function createCliRequest(): RequestInterface
    {
        $data = $this->cliParser->parse($_SERVER['argv'] ?? []);
        $route = (string) ($data[Cli\Parser::REQUEST_ROUTE] ?? '');
        unset($data[Cli\Parser::REQUEST_ROUTE]);

        return new Request(RequestInterface::METHOD_CLI, $route, $data);
    }

    private function createHttpRequest(): RequestInterface
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        $route = '/' . trim(is_string($path) ? rawurldecode($path) : '', '/');

        return new Request($method, $route, $this->httpParser->parse($method));
    }
}

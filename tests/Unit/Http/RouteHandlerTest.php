<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Codeception\Test\Unit;
use Core\Http\Exception\HttpException;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotFoundException;
use Core\Http\RouteHandler;
use Psr\Http\Message\ResponseInterface;
use Tests\Support\Requests;
use Tests\Support\TestApplication;

/**
 * The route handler wired by the container with the test routes (tests/Support/Data/etc/routes.xml).
 */
final class RouteHandlerTest extends Unit
{
    private RouteHandler $handler;

    protected function _before(): void
    {
        $handler = TestApplication::createContainer()->create(RouteHandler::class);
        \assert($handler instanceof RouteHandler);
        $this->handler = $handler;
    }

    public function testActionResultIsNegotiated(): void
    {
        $json = $this->handle('GET', '/items/7');
        $this->assertSame(['application/json', '{"id":7,"name":"Item 7"}', 'Accept'], [$json->getHeaderLine('Content-Type'), (string) $json->getBody(), $json->getHeaderLine('Vary')]);

        $html = $this->handle('GET', '/items/7', ['Accept' => 'text/html']);
        $this->assertSame('<h1>Item 7</h1>', (string) $html->getBody(), 'template');

        $xml = $this->handle('GET', '/items/7', ['Accept' => 'application/xml']);
        $this->assertStringContainsString('<response><id>7</id><name>Item 7</name></response>', (string) $xml->getBody());
    }

    public function testRouteAttributesAndQueryDefaults(): void
    {
        $data = $this->json('GET', '/archive/2026/hello%20world?x=1');
        $this->assertSame(['archive', ['year' => '2026', 'slug' => 'hello world'], ['x' => '1']], [$data['route'], $data['params'], $data['query']]);

        $list = $this->json('GET', '/items?page=2');
        $this->assertSame([2, 3, '/items?page=3&limit=3'], [$list['page'], $list['limit'], $list['next']], 'defaults and generated URL');
        $this->assertSame(4, $list['items'][0]['id']);
    }

    public function testResultTypes(): void
    {
        $this->assertSame([204, ''], [$this->handle('DELETE', '/items/1')->getStatusCode(), (string) $this->handle('DELETE', '/items/1')->getBody()], 'null');
        $this->assertSame('text/html; charset=utf-8', $this->handle('GET', '/')->getHeaderLine('Content-Type'), 'string');
        $this->assertSame('yes', $this->handle('GET', '/echo')->getHeaderLine('X-Echo'), 'PSR-15 handler');
    }

    public function testTrailingSlashRedirects(): void
    {
        $get = $this->handle('GET', '/items/?page=2');
        $this->assertSame([301, '/items?page=2'], [$get->getStatusCode(), $get->getHeaderLine('Location')]);

        $docs = $this->handle('HEAD', '/docs');
        $this->assertSame([301, '/docs/'], [$docs->getStatusCode(), $docs->getHeaderLine('Location')]);

        $put = $this->handle('PUT', '/items/5/');
        $this->assertSame([308, '/items/5'], [$put->getStatusCode(), $put->getHeaderLine('Location')], 'keeps the method');
    }

    public function testStrictAndIgnorePolicies(): void
    {
        $this->assertSame('strict', json_decode((string) $this->handle('GET', '/strict')->getBody(), true)['route']);
        try {
            $this->handle('GET', '/strict/');
            $this->fail('404 expected: strict route');
        } catch (NotFoundException) {
            $this->assertTrue(true);
        }

        foreach (['/lenient/5', '/lenient/5/'] as $path) {
            $data = json_decode((string) $this->handle('GET', $path)->getBody(), true);
            $this->assertSame(['lenient', ['id' => '5']], [$data['route'], $data['params']], $path);
        }
    }

    public function testOptionsAndMethodNotAllowed(): void
    {
        $options = $this->handle('OPTIONS', '/items/1');
        $this->assertSame([204, 'GET, HEAD, PUT, PATCH, DELETE, OPTIONS'], [$options->getStatusCode(), $options->getHeaderLine('Allow')]);
        $this->assertSame('options.explicit', $this->handle('OPTIONS', '/custom-options')->getHeaderLine('X-Route'));

        $this->expectException(MethodNotAllowedException::class);
        $this->handle('DELETE', '/items');
    }

    public function testHead(): void
    {
        $this->assertSame('head.explicit', $this->handle('HEAD', '/head')->getHeaderLine('X-Route'));
        $this->assertSame('head.get', $this->handle('GET', '/head')->getHeaderLine('X-Route'));
        $this->assertSame('application/json', $this->handle('HEAD', '/items/1')->getHeaderLine('Content-Type'), 'GET route');
    }

    public function testErrors(): void
    {
        foreach ([['GET', '/search', 400], ['GET', '/items/101', 404], ['GET', '/nope', 404]] as [$method, $path, $status]) {
            try {
                $this->handle($method, $path);
                $this->fail("$status expected for $path");
            } catch (HttpException $e) {
                $this->assertSame($status, $e->getStatusCode(), $path);
            }
        }
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement');
        $this->handle('GET', '/not-an-action');
    }

    public function testFileResponsesArePrepared(): void
    {
        $response = $this->handle('GET', '/download/hello.txt', ['Range' => 'bytes=0-4']);

        $this->assertSame([206, 'Hello'], [$response->getStatusCode(), (string) $response->getBody()]);
    }

    /**
     * @param array<string, string> $headers
     */
    private function handle(string $method, string $uri, array $headers = []): ResponseInterface
    {
        return $this->handler->handle(Requests::make($method, $uri, $headers));
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string $method, string $uri): array
    {
        return json_decode((string) $this->handle($method, $uri)->getBody(), true, flags: JSON_THROW_ON_ERROR);
    }
}

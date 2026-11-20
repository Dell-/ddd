<?php

declare(strict_types=1);

namespace Tests\Unit\Request;

use Codeception\Test\Unit;
use Core\Di\Config;
use Core\Di\Config\Argument\Service as ArgumentService;
use Core\Di\Config\Argument\TypeFactory;
use Core\Di\Config\Xml\Converter;
use Core\Di\Container;
use Core\Request\Dispatcher;
use Core\Request\Request;
use Core\Request\RequestInterface;
use Core\Route\Config as RouteConfig;
use Core\Route\Config\Xml\Converter as RouteConverter;
use Core\Route\NotFoundException;
use Tests\Support\StringReader;

final class DispatcherTest extends Unit
{
    private Dispatcher $dispatcher;

    protected function _before(): void
    {
        $container = new Container(new Config(
            new StringReader('<di/>'),
            new Converter(new ArgumentService(new TypeFactory()))
        ));
        $routes = new RouteConfig(
            new StringReader(
                '<routes>'
                . '<route method="CLI" name="echo" action="Tests\Support\Fixture\EchoAction"/>'
                . '<route method="CLI" name="broken" action="Tests\Support\Fixture\NotAnAction"/>'
                . '</routes>'
            ),
            new RouteConverter()
        );
        $this->dispatcher = new Dispatcher($container, $routes);
    }

    public function testActionGetsTheRequest(): void
    {
        $request = new Request(RequestInterface::METHOD_CLI, 'echo', ['name' => 'test']);

        $this->assertSame('echo:test', $this->dispatcher->handle($request));
    }

    public function testUnknownRouteIsNotFound(): void
    {
        try {
            $this->dispatcher->handle(new Request('GET', 'echo'));
            $this->fail('NotFoundException expected');
        } catch (NotFoundException $e) {
            $this->assertSame(404, $e->getCode());
            $this->assertSame('Route "GET echo" not found.', $e->getMessage());
        }
    }

    public function testActionMustImplementActionInterface(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must implement');
        $this->dispatcher->handle(new Request(RequestInterface::METHOD_CLI, 'broken'));
    }
}

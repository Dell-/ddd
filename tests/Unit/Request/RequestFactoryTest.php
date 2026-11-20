<?php

declare(strict_types=1);

namespace Tests\Unit\Request;

use Codeception\Test\Unit;
use Core\Request\Cli\Parser as CliParser;
use Core\Request\Http\Parser as HttpParser;
use Core\Request\RequestFactory;
use Core\Request\RequestInterface;

final class RequestFactoryTest extends Unit
{
    /**
     * @var list<string>|null
     */
    private ?array $argv;

    protected function _before(): void
    {
        $this->argv = $_SERVER['argv'] ?? null;
    }

    protected function _after(): void
    {
        $_SERVER['argv'] = $this->argv;
    }

    public function testConsoleRequest(): void
    {
        $_SERVER['argv'] = ['bin/console', 'hello', '--name=World'];

        $request = (new RequestFactory(new HttpParser(), new CliParser()))->create();

        $this->assertSame(RequestInterface::METHOD_CLI, $request->getMethod());
        $this->assertSame('hello', $request->getRoute());
        $this->assertSame(['name' => 'World'], $request->getAllData());
        $this->assertSame('World', $request->getData('name'));
        $this->assertSame('default', $request->getData('missing', 'default'));
    }
}

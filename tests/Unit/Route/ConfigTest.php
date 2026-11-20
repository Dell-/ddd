<?php

declare(strict_types=1);

namespace Tests\Unit\Route;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Core\Config\ReaderInterface;
use Core\Route\Config;
use Core\Route\Config\Xml\Converter;
use Core\Route\TrailingSlash;
use Tests\Support\StringReader;

final class ConfigTest extends Unit
{
    public function testRoutesAndCommands(): void
    {
        $config = new Config(new StringReader(
            '<routes>'
            . '<route name="b" method="GET" path="/b" action="\B"><query name="page" requirement="\d+" default="1"/><query name="q" required="true"/></route>'
            . '<route name="a" method=" PUT   PATCH " path="/a/{id}" action="A"/>'
            . '<command name="hello" action="\Hello"/>'
            . '</routes>'
        ), new Converter());

        $this->assertSame(['b', 'a'], array_keys($config->getRoutes()), 'declaration order');
        $this->assertSame('B', $config->getRoute('b')?->action);
        $this->assertSame(['PUT', 'PATCH'], $config->getRoute('a')?->methods);
        $query = $config->getRoute('b')?->query ?? [];
        $this->assertSame(['\d+', '1', false], [$query['page']->requirement, $query['page']->default, $query['page']->required]);
        $this->assertTrue($query['q']->required);
        $this->assertNull($config->getRoute('missing'));
        $this->assertSame('Hello', $config->getCommand('hello'));
    }

    public function testTrailingSlashPolicy(): void
    {
        $routes = static fn (string $xml): array => new Config(new StringReader($xml), new Converter())->getRoutes();

        $default = $routes('<routes><route name="a" method="GET" path="/a" action="A"/></routes>');
        $this->assertSame(TrailingSlash::Redirect, $default['a']->trailingSlash, 'redirect when omitted');

        $configured = $routes('<routes trailing-slash="strict"><route name="a" method="GET" path="/a" action="A"/>'
            . '<route name="b" method="GET" path="/b" action="B" trailing-slash="ignore"/></routes>');
        $this->assertSame([TrailingSlash::Strict, TrailingSlash::Ignore], [$configured['a']->trailingSlash, $configured['b']->trailingSlash]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid trailing-slash "sometimes", expected one of: redirect, strict, ignore.');
        $routes('<routes trailing-slash="sometimes"/>');
    }

    public function testDuplicateName(): void
    {
        $config = new Config(new StringReader('<routes><route name="a" method="GET" path="/a" action="A"/><route name="a" method="GET" path="/b" action="B"/></routes>'), new Converter());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate route name "a"');
        $config->getRoutes();
    }

    public function testReadOnce(): void
    {
        $reader = $this->makeEmpty(ReaderInterface::class, ['read' => Expected::once(static fn (): \DOMDocument => new StringReader('<routes/>')->read())]);
        $config = new Config($reader, new Converter());

        $config->getRoutes();
        $config->getCommand('x');
        $config->getRoute('x');
    }
}

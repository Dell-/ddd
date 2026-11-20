<?php

declare(strict_types=1);

namespace Tests\Unit\Route;

use Codeception\Test\Unit;
use Core\Http\Exception\HttpException;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotFoundException;
use Core\Route\Config;
use Core\Route\Config\Xml\Converter;
use Core\Route\Router;
use Tests\Support\StringReader;

final class RouterTest extends Unit
{
    private Router $router;

    protected function _before(): void
    {
        $this->router = new Router(new Config(new StringReader(
            '<routes>'
            . '<route name="item.list" method="GET" path="/items" action="List"><query name="page" requirement="\d+" default="1"/></route>'
            . '<route name="item.create" method="POST" path="/items" action="Create"/>'
            . '<route name="item.new" method="GET" path="/items/new" action="New"/>'
            . '<route name="item.show" method="GET" path="/items/{id}" action="Show"/>'
            . '<route name="item.update" method="PUT PATCH" path="/items/{id:\d+}" action="Update"/>'
            . '<route name="item.delete" method="DELETE" path="/items/{id:\d+}" action="Delete"/>'
            . '<route name="docs" method="GET" path="/docs/" action="Docs"/>'
            . '<route name="strict" method="GET" path="/strict" action="Strict" trailing-slash="strict"/>'
            . '<route name="lenient" method="GET PUT" path="/lenient/{id}" action="Lenient" trailing-slash="ignore"/>'
            . '<route name="tag" method="GET" path="/tags/{tag}" action="Tag"/>'
            . '<route name="file" method="GET" path="/files/{path:.+}" action="File"/>'
            . '<route name="head.get" method="GET" path="/head" action="HeadGet"/>'
            . '<route name="head.explicit" method="HEAD" path="/head" action="HeadExplicit"/>'
            . '<route name="opt" method="OPTIONS" path="/opt" action="Options"/>'
            . '<route name="post.only" method="POST" path="/post" action="Post"/>'
            . '<command name="hello" action="Hello"/>'
            . '</routes>'
        ), new Converter()));
    }

    public function testMatch(): void
    {
        $match = $this->router->match('GET', '/items/42');

        $this->assertSame('item.show', $match->route->name);
        $this->assertSame(['id' => '42'], $match->parameters);
        $this->assertSame('Create', $this->router->match('POST', '/items')->route->action);
        $this->assertSame('Update', $this->router->match('patch', '/items/1')->route->action);
        $this->assertSame('item.new', $this->router->match('GET', '/items/new')->route->name, 'first match wins');
    }

    public function testQueryDefaultsAndValidation(): void
    {
        $this->assertSame(['page' => '1'], $this->router->match('GET', '/items')->query);
        $this->assertSame(['page' => '3', 'x' => 'y'], $this->router->match('GET', '/items', ['page' => '3', 'x' => 'y'])->query);

        try {
            $this->router->match('GET', '/items', ['page' => 'abc']);
            $this->fail('400 expected');
        } catch (HttpException $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame(['errors' => ['page' => 'must match "\d+"']], $e->getExtensions());
            $this->assertSame('Invalid query parameters: "page" must match "\d+".', $e->getDetail());
        }
    }

    public function testTrailingSlashIsSignificant(): void
    {
        $this->assertSame('docs', $this->router->match('GET', '/docs/')->route->name);
        foreach (['/items/', '/docs'] as $path) {
            try {
                $this->router->match('GET', $path);
                $this->fail('404 expected for ' . $path);
            } catch (NotFoundException) {
                $this->assertTrue(true);
            }
        }
    }

    public function testCanonicalPath(): void
    {
        $this->assertSame('/items', $this->router->findCanonicalPath('/items/'));
        $this->assertSame('/docs/', $this->router->findCanonicalPath('/docs'));
        $this->assertSame('/items/5', $this->router->findCanonicalPath('/items/5/'));
        $this->assertNull($this->router->findCanonicalPath('/nope/'));
        $this->assertNull($this->router->findCanonicalPath('/'));
        $this->assertNull($this->router->findCanonicalPath('/items'), 'already canonical');
    }

    public function testTrailingSlashPolicies(): void
    {
        // redirect (default): the other form is not matched, but has a canonical path
        $this->assertSame('/items', $this->router->findCanonicalPath('/items/'));

        // strict: no canonical path, so 404
        $this->assertSame('strict', $this->router->match('GET', '/strict')->route->name);
        $this->assertNull($this->router->findCanonicalPath('/strict/'));
        try {
            $this->router->match('GET', '/strict/');
            $this->fail('404 expected');
        } catch (NotFoundException) {
            $this->assertTrue(true);
        }

        // ignore: both forms match, URLs are generated in the configured form
        $this->assertSame(['id' => '5'], $this->router->match('GET', '/lenient/5/')->parameters);
        $this->assertSame(['id' => '5'], $this->router->match('PUT', '/lenient/5')->parameters);
        $this->assertNull($this->router->findCanonicalPath('/lenient/5/'));
        $this->assertSame(['GET', 'HEAD', 'PUT', 'OPTIONS'], $this->router->getAllowedMethods('/lenient/5/'));
        $this->assertSame('/lenient/5', $this->router->generate('lenient', ['id' => 5]));
    }

    public function testPercentEncoding(): void
    {
        $this->assertSame(['tag' => 'a b'], $this->router->match('GET', '/tags/a%20b')->parameters);
        $this->assertSame(['tag' => 'кіт'], $this->router->match('GET', '/tags/%D0%BA%D1%96%D1%82')->parameters);
        $this->assertSame(['tag' => 'a/b'], $this->router->match('GET', '/tags/a%2Fb')->parameters, 'encoded slash stays inside');
        $this->assertSame(['tag' => 'a%2Fb'], $this->router->match('GET', '/tags/a%252Fb')->parameters, 'no double decoding');
        $this->assertSame(['path' => 'a/b c'], $this->router->match('GET', '/files/a/b%20c')->parameters);
        $this->expectException(NotFoundException::class);
        $this->router->match('GET', '/items%2F42');
    }

    public function testNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('No route for "GET /nope".');
        $this->router->match('GET', '/nope');
    }

    public function testMethodNotAllowed(): void
    {
        foreach ([
            ['DELETE', '/items', ['GET', 'HEAD', 'POST', 'OPTIONS']],
            ['POST', '/items/1', ['GET', 'HEAD', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']],
            ['PUT', '/items/abc', ['GET', 'HEAD', 'OPTIONS']],
            ['TRACE', '/items', ['GET', 'HEAD', 'POST', 'OPTIONS']],
            ['HEAD', '/post', ['POST', 'OPTIONS']],
            ['OPTIONS', '/items', ['GET', 'HEAD', 'POST', 'OPTIONS']],
        ] as [$method, $path, $allowed]) {
            try {
                $this->router->match($method, $path);
                $this->fail("405 expected for $method $path");
            } catch (MethodNotAllowedException $e) {
                $this->assertSame($allowed, $e->getAllowedMethods(), "$method $path");
            }
        }
    }

    public function testHead(): void
    {
        $this->assertSame('item.show', $this->router->match('HEAD', '/items/1')->route->name, 'served by GET');
        $this->assertSame('head.explicit', $this->router->match('HEAD', '/head')->route->name, 'explicit HEAD wins');
        $this->assertSame('opt', $this->router->match('OPTIONS', '/opt')->route->name);
    }

    public function testAllowedMethods(): void
    {
        $this->assertSame(['GET', 'HEAD', 'POST', 'OPTIONS'], $this->router->getAllowedMethods('/items'));
        $this->assertSame(['OPTIONS'], $this->router->getAllowedMethods('/opt'));
        $this->assertSame([], $this->router->getAllowedMethods('/nope'));
    }

    public function testCommands(): void
    {
        $this->assertSame('Hello', $this->router->matchCommand('hello'));
        $this->expectException(NotFoundException::class);
        $this->router->matchCommand('nope');
    }

    public function testGenerate(): void
    {
        $this->assertSame('/items', $this->router->generate('item.list'));
        $this->assertSame('/items?page=2', $this->router->generate('item.list', ['page' => 2]));
        $this->assertSame('/items/42', $this->router->generate('item.update', ['id' => 42]));
        $this->assertSame('/docs/', $this->router->generate('docs'));

        $url = $this->router->generate('tag', ['tag' => 'a/b c%']);
        $this->assertSame(['tag' => 'a/b c%'], $this->router->match('GET', $url)->parameters, 'round trip');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Route "nope" does not exist.');
        $this->router->generate('nope');
    }

    public function testGenerateRejectsUndeclaredParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('has no parameter "sort"');
        $this->router->generate('item.list', ['sort' => 'name']);
    }
}

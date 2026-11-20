<?php

declare(strict_types=1);

namespace Tests\Unit\Route;

use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Core\Route\QueryParameter;
use Core\Route\Route;

final class RouteTest extends Unit
{
    public function testProperties(): void
    {
        $route = new Route('item.update', ['put', 'PATCH', 'PUT'], '/items/{id:\d+}', 'Action', [new QueryParameter('force')]);

        $this->assertSame('item.update', $route->name);
        $this->assertSame(['PUT', 'PATCH'], $route->methods);
        $this->assertSame('/items/{id:\d+}', $route->path);
        $this->assertSame('Action', $route->action);
        $this->assertSame(['id'], $route->parameterNames);
        $this->assertSame(['force'], array_keys($route->query));
        $this->assertTrue($route->allowsMethod('patch'));
        $this->assertFalse($route->allowsMethod('GET'));
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>|null}>
     */
    public static function matchProvider(): iterable
    {
        yield 'root' => ['/', '/', []];
        yield 'static' => ['/items', '/items', []];
        yield 'trailing slash is significant' => ['/items', '/items/', null];
        yield 'trailing slash route' => ['/docs/', '/docs/', []];
        yield 'trailing slash route without slash' => ['/docs/', '/docs', null];
        yield 'parameter' => ['/items/{id}', '/items/abc', ['id' => 'abc']];
        yield 'one segment' => ['/items/{id}', '/items/a/b', null];
        yield 'not empty' => ['/items/{id}', '/items/', null];
        yield 'regex' => ['/items/{id:\d+}', '/items/42', ['id' => '42']];
        yield 'regex mismatch' => ['/items/{id:\d+}', '/items/4x', null];
        yield 'quantifier braces' => ['/archive/{year:\d{4}}', '/archive/2026', ['year' => '2026']];
        yield 'several' => ['/a/{x}/b/{y}', '/a/1/b/2', ['x' => '1', 'y' => '2']];
        yield 'inside a segment' => ['/file/{name}.{ext:[a-z]+}', '/file/report.pdf', ['name' => 'report', 'ext' => 'pdf']];
        yield 'multi-segment' => ['/files/{path:.+}', '/files/a/b/c.txt', ['path' => 'a/b/c.txt']];
        yield 'alternation anchored' => ['/{lang:en|uk}', '/enx', null];
        yield 'unicode' => ['/tags/{tag}', '/tags/кіт', ['tag' => 'кіт']];
        yield 'literal dot' => ['/a.b', '/axb', null];
        yield 'no newline' => ['/items', "/items\n", null];
    }

    /**
     * @param array<string, string>|null $expected
     */
    #[DataProvider('matchProvider')]
    public function testMatch(string $pattern, string $path, ?array $expected): void
    {
        $this->assertSame($expected, new Route('r', ['GET'], $pattern, 'A')->match($path));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidProvider(): iterable
    {
        yield 'no leading slash' => ['items', 'must start with "/"'];
        yield 'unclosed' => ['/items/{id', 'unclosed "{"'];
        yield 'unexpected' => ['/items/id}', 'unexpected "}"'];
        yield 'name' => ['/items/{1d}', 'invalid parameter name "1d"'];
        yield 'duplicate' => ['/{id}/{id}', 'duplicate parameter "id"'];
        yield 'regex' => ['/items/{id:[}', 'invalid regex'];
        yield 'empty regex' => ['/items/{id:}', 'invalid regex'];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalid(string $path, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new Route('r', ['GET'], $path, 'A');
    }

    public function testNameClash(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is a path and a query parameter');
        new Route('r', ['GET'], '/{id}', 'A', [new QueryParameter('id')]);
    }

    public function testQuery(): void
    {
        $route = new Route('r', ['GET'], '/items', 'A', [
            new QueryParameter('page', '\d+', '1'),
            new QueryParameter('q', required: true),
            new QueryParameter('tags', '[a-z]+'),
        ]);

        $this->assertEquals([['q' => 'x', 'page' => '1', 'other' => 'kept'], []], $route->matchQuery(['q' => 'x', 'other' => 'kept']));
        $this->assertSame([['page' => '2', 'q' => 'x', 'tags' => ['a', 'b']], []], $route->matchQuery(['page' => '2', 'q' => 'x', 'tags' => ['a', 'b']]));
        $this->assertSame(['page' => 'must match "\\d+"', 'q' => 'is required', 'tags' => 'must match "[a-z]+"'], $route->matchQuery(['page' => 'x', 'tags' => ['a', '1']])[1], 'declaration order');
    }

    public function testGeneratePutsParametersWhereTheyAreDeclared(): void
    {
        $route = new Route('r', ['GET'], '/items/{id:\d+}', 'A', [new QueryParameter('page', '\d+', '1'), new QueryParameter('sort')]);

        $this->assertSame('/items/5', $route->generate(['id' => 5]));
        $this->assertSame('/items/5?page=2&sort=name%20desc', $route->generate(['sort' => 'name desc', 'id' => 5, 'page' => 2]));
        $this->assertSame('/items/5?page=1&sort=a', $route->generate(['id' => 5, 'sort' => 'a', 'page' => true]), 'declaration order, bool as 1');
        $this->assertSame('/items/5', $route->generate(['id' => 5, 'sort' => null]), 'null is omitted');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function generateErrorProvider(): iterable
    {
        yield 'undeclared' => [['id' => 1, 'color' => 'red'], 'has no parameter "color" (path: id; query: page)'];
        yield 'missing path parameter' => [[], 'requires path parameter "id"'];
        yield 'path regex' => [['id' => 'abc'], 'must match "\d+"'];
        yield 'query regex' => [['id' => 1, 'page' => 'x'], 'Query parameter "page" of route "r" must match'];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('generateErrorProvider')]
    public function testGenerateErrors(array $parameters, string $message): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        new Route('r', ['GET'], '/items/{id:\d+}', 'A', [new QueryParameter('page', '\d+')])->generate($parameters);
    }

    public function testGenerateRequiredQuery(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"q" of route "search" is required');
        new Route('search', ['GET'], '/search', 'A', [new QueryParameter('q', required: true)])->generate([]);
    }

    public function testGenerateEncoding(): void
    {
        $this->assertSame('/tags/a%20b%3F%D0%BA', new Route('r', ['GET'], '/tags/{tag}', 'A')->generate(['tag' => 'a b?к']));
        $this->assertSame('/tags/a%2Fb', new Route('r', ['GET'], '/tags/{tag}', 'A')->generate(['tag' => 'a/b']));
        $this->assertSame('/files/a/b%20c.txt', new Route('r', ['GET'], '/files/{path:.+}', 'A')->generate(['path' => 'a/b c.txt']));
        $this->expectException(\InvalidArgumentException::class);
        new Route('r', ['GET'], '/tags/{tag:[a-z]+}', 'A')->generate(['tag' => 'a/b']);
    }

    public function testInvalidQueryRegex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new QueryParameter('p', '[');
    }
}

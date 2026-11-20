<?php

declare(strict_types=1);

namespace Tests\Unit\Request\Http;

use Codeception\Test\Unit;
use Core\Request\Http\Parser;
use Core\Request\RequestException;

final class ParserTest extends Unit
{
    /**
     * @var array<array-key, mixed>
     */
    private array $get;

    /**
     * @var array<array-key, mixed>
     */
    private array $post;

    protected function _before(): void
    {
        [$this->get, $this->post] = [$_GET, $_POST];
        $_GET = ['q' => 'get'];
        $_POST = ['q' => 'post'];
    }

    protected function _after(): void
    {
        [$_GET, $_POST] = [$this->get, $this->post];
    }

    public function testQueryMethodsUseQueryString(): void
    {
        foreach (['GET', 'head', 'OPTIONS', 'DELETE'] as $method) {
            $this->assertSame(['q' => 'get'], (new Parser())->parse($method), $method);
        }
    }

    public function testPostUsesFormData(): void
    {
        $this->assertSame(['q' => 'post'], (new Parser())->parse('POST'));
    }

    public function testEmptyBodyGivesNoData(): void
    {
        $_POST = [];

        $this->assertSame([], (new Parser())->parse('POST'));
        $this->assertSame([], (new Parser())->parse('PUT'));
    }

    public function testUnsupportedMethod(): void
    {
        $this->expectException(RequestException::class);
        $this->expectExceptionCode(405);
        (new Parser())->parse('TRACE');
    }
}

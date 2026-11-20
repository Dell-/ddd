<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Header;

use Codeception\Test\Unit;
use Core\Http\Header\HeaderValue;

final class HeaderValueTest extends Unit
{
    public function testTokens(): void
    {
        $this->assertTrue(HeaderValue::isToken('Content-Type'));
        $this->assertTrue(HeaderValue::isToken("!#$%&'*+-.^_`|~"));
        $this->assertFalse(HeaderValue::isToken('a b'));
        $this->assertFalse(HeaderValue::isToken('a:b'));
        $this->assertFalse(HeaderValue::isToken(''));
    }

    public function testSplitListRespectsQuotes(): void
    {
        $this->assertSame(['a', 'b="x, y"', 'c'], HeaderValue::splitList(' a , b="x, y",, c '));
        $this->assertSame(['"a\\", b"'], HeaderValue::splitList('"a\\", b"'));
    }

    public function testParameters(): void
    {
        $this->assertSame(
            ['form-data', ['name' => 'a;b', 'filename' => 'x "y".txt', 'size' => '3']],
            HeaderValue::parseParameters('form-data; name="a;b"; FILENAME="x \\"y\\".txt"; size=3; size=4; broken')
        );
    }

    public function testQuote(): void
    {
        $this->assertSame('utf-8', HeaderValue::quote('utf-8'));
        $this->assertSame('"a b"', HeaderValue::quote('a b'));
        $this->assertSame('"a\\"b\\\\"', HeaderValue::quote('a"b\\'));
        $this->assertSame('a"b\\', HeaderValue::unquote(HeaderValue::quote('a"b\\')));
    }
}

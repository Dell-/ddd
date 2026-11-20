<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Body;

use Codeception\Test\Unit;
use Core\Http\Body\FormData;

final class FormDataTest extends Unit
{
    public function testUrlencoded(): void
    {
        $this->assertSame(
            ['a.b' => 'x y', 'c d' => '1', 'list' => ['1', '2'], 'map' => ['k' => ['n' => 'v']], 'empty' => '', 'only' => ''],
            FormData::parseUrlencoded('a.b=x+y&c+d=1&list[]=1&list[]=2&map[k][n]=v&empty=&only&&=ignored')
        );
    }

    public function testNamesAreNotMangled(): void
    {
        parse_str('a.b=1&c d=2', $php);

        $this->assertSame(['a_b' => '1', 'c_d' => '2'], $php, 'what parse_str does');
        $this->assertSame(['a.b' => '1', 'c d' => '2'], FormData::parseUrlencoded('a.b=1&c%20d=2'));
    }

    public function testBrackets(): void
    {
        $data = [];
        FormData::add($data, 'a[][x]', '1');
        FormData::add($data, 'a[][x]', '2');
        FormData::add($data, 'broken[', '3');
        FormData::add($data, '[x]', '4');
        FormData::add($data, 'str', '5');
        FormData::add($data, 'str[k]', '6');

        $this->assertSame(['a' => [['x' => '1'], ['x' => '2']], 'broken[' => '3', '[x]' => '4', 'str' => ['k' => '6']], $data);
    }

    public function testDepthIsLimited(): void
    {
        $data = [];
        FormData::add($data, 'a' . str_repeat('[x]', 100), 'deep');

        $depth = 0;
        $node = $data;
        while (is_array($node)) {
            $node = reset($node);
            $depth++;
        }
        $this->assertSame(32, $depth);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Route;

use Codeception\Test\Unit;
use Core\Route\Config;
use Core\Route\Config\Xml\Converter;
use Tests\Support\StringReader;

final class ConfigTest extends Unit
{
    public function testMatch(): void
    {
        $config = new Config(
            new StringReader(
                '<routes>'
                . '<route method="GET" name="/" action="\Index"/>'
                . '<route method="POST" name="/" action="Save"/>'
                . '<route method="CLI" name="hello" action="Hello"/>'
                . '</routes>'
            ),
            new Converter()
        );

        $this->assertSame('Index', $config->match('GET', '/'));
        $this->assertSame('Index', $config->match('get', '/'));
        $this->assertSame('Save', $config->match('POST', '/'));
        $this->assertSame('Hello', $config->match('CLI', 'hello'));
        $this->assertNull($config->match('PUT', '/'));
        $this->assertNull($config->match('GET', '/missing'));
    }

    public function testRoutesAreReadOnce(): void
    {
        $reader = $this->makeEmpty(
            \Core\Config\ReaderInterface::class,
            ['read' => \Codeception\Stub\Expected::once(static function (): \DOMDocument {
                $document = new \DOMDocument();
                $document->loadXML('<routes><route method="GET" name="/" action="A"/></routes>');

                return $document;
            })]
        );
        $config = new Config($reader, new Converter());

        $config->match('GET', '/');
        $config->match('GET', '/x');
    }
}

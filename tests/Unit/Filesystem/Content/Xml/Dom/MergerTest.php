<?php

declare(strict_types=1);

namespace Tests\Unit\Filesystem\Content\Xml\Dom;

use Codeception\Test\Unit;
use Core\Di\Config\Xml\MergeRules as DiMergeRules;
use Core\Filesystem\Content\Xml\Dom\MergeRules;
use Core\Filesystem\Content\Xml\Dom\Merger;
use Core\Route\Config\Xml\MergeRules as RouteMergeRules;
use Codeception\Attribute\DataProvider;

final class MergerTest extends Unit
{
    /**
     * @return iterable<string, array{MergeRules, string, list<string>}>
     */
    public static function mergeProvider(): iterable
    {
        $di = new DiMergeRules();
        $routes = new RouteMergeRules();
        $default = new MergeRules();

        yield 'single document is returned as is' => [
            $di,
            '<di><interface name="I" class="A"/></di>',
            ['<di><interface name="I" class="A"/></di>'],
        ];
        yield 'di: interface class is overridden' => [
            $di,
            '<di><interface name="I" class="B"/></di>',
            ['<di><interface name="I" class="A"/></di>', '<di><interface name="I" class="B"/></di>'],
        ];
        yield 'di: different interfaces are appended' => [
            $di,
            '<di><interface name="I" class="A"/><interface name="J" class="B"/></di>',
            ['<di><interface name="I" class="A"/></di>', '<di><interface name="J" class="B"/></di>'],
        ];
        yield 'di: instance is merged by class, arguments by name' => [
            $di,
            '<di><instance class="X" shared="true">'
            . '<argument name="a"><int>1</int></argument><argument name="b"><int>2</int></argument>'
            . '</instance></di>',
            [
                '<di><instance class="X"><argument name="a"><int>1</int></argument></instance></di>',
                '<di><instance class="X" shared="true"><argument name="b"><int>2</int></argument></instance></di>',
            ],
        ];
        yield 'di: argument value is replaced as a whole (whitespace, other type)' => [
            $di,
            '<di><instance class="X"><argument name="a"><int>5</int></argument></instance></di>',
            [
                "<di><instance class=\"X\">\n  <argument name=\"a\">\n    <string>old</string>\n  </argument>\n</instance></di>",
                "<di><instance class=\"X\">\n  <argument name=\"a\">\n    <int>5</int>\n  </argument>\n</instance></di>",
            ],
        ];
        yield 'di: array argument is replaced as a whole' => [
            $di,
            '<di><instance class="X"><argument name="a"><array><item><int>3</int></item></array></argument></instance></di>',
            [
                '<di><instance class="X"><argument name="a"><array><item><int>1</int></item><item><int>2</int></item></array></argument></instance></di>',
                '<di><instance class="X"><argument name="a"><array><item><int>3</int></item></array></argument></instance></di>',
            ],
        ];
        yield 'di: callbacks are appended' => [
            $di,
            '<di><instance class="L"><callback method="push"/><callback method="push"/></instance></di>',
            [
                '<di><instance class="L"><callback method="push"/></instance></di>',
                '<di><instance class="L"><callback method="push"/></instance></di>',
            ],
        ];
        yield 'routes: same name and other method is appended' => [
            $routes,
            '<routes><route method="GET" name="/" action="A"/><route method="POST" name="/" action="B"/></routes>',
            [
                '<routes><route method="GET" name="/" action="A"/></routes>',
                '<routes><route method="POST" name="/" action="B"/></routes>',
            ],
        ];
        yield 'routes: action is overridden' => [
            $routes,
            '<routes><route method="GET" name="/" action="B"/></routes>',
            [
                '<routes><route method="GET" name="/" action="A"/></routes>',
                '<routes><route method="GET" name="/" action="B"/></routes>',
            ],
        ];
        yield 'default rules: text is replaced' => [
            $default,
            '<cfg><item name="x">new</item></cfg>',
            ['<cfg><item name="x">old</item></cfg>', '<cfg><item name="x">new</item></cfg>'],
        ];
        yield 'element without content keeps the old content' => [
            $default,
            '<cfg><item name="x" flag="1">old</item></cfg>',
            ['<cfg><item name="x">old</item></cfg>', '<cfg><item name="x" flag="1"/></cfg>'],
        ];
        yield 'element without key attribute is appended' => [
            $default,
            '<cfg><item>1</item><item>2</item></cfg>',
            ['<cfg><item>1</item></cfg>', '<cfg><item>2</item></cfg>'],
        ];
        yield 'key with quotes' => [
            $default,
            '<cfg><item name="a&quot;b\'c">2</item></cfg>',
            ['<cfg><item name="a&quot;b\'c">1</item></cfg>', '<cfg><item name="a&quot;b\'c">2</item></cfg>'],
        ];
        yield 'default namespace' => [
            $default,
            '<cfg xmlns="urn:x"><item name="a">2</item></cfg>',
            ['<cfg xmlns="urn:x"><item name="a">1</item></cfg>', '<cfg xmlns="urn:x"><item name="a">2</item></cfg>'],
        ];
        yield 'match only among siblings of the matched parent' => [
            $default,
            '<cfg><g name="1"><item name="a">1</item></g><g name="2"><item name="a">2</item></g></cfg>',
            ['<cfg><g name="1"><item name="a">1</item></g></cfg>', '<cfg><g name="2"><item name="a">2</item></g></cfg>'],
        ];
        yield 'root attributes are merged' => [
            $default,
            '<cfg a="1" b="3"/>',
            ['<cfg a="1" b="2"/>', '<cfg b="3"/>'],
        ];
        yield 'three documents, the last one wins' => [
            $default,
            '<cfg><item name="x">3</item></cfg>',
            ['<cfg><item name="x">1</item></cfg>', '<cfg><item name="x">2</item></cfg>', '<cfg><item name="x">3</item></cfg>'],
        ];
    }

    /**
     * @param list<string> $documents
     */
    #[DataProvider('mergeProvider')]
    public function testMerge(MergeRules $rules, string $expected, array $documents): void
    {
        $merger = new Merger($rules);
        foreach ($documents as $document) {
            $merger->merge($document);
        }

        $this->assertXmlStringEqualsXmlString($expected, $this->normalize($merger->getContent()));
    }

    public function testDifferentRootElementsAreRejected(): void
    {
        $merger = new Merger();
        $merger->merge('<a/>');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('root elements differ');
        $merger->merge('<b/>');
    }

    public function testInvalidXmlIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML document');
        (new Merger())->merge('<a>');
    }

    public function testSchemaValidationFailureIsReported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Duplicate key-sequence ['GET', '/']");
        (new Merger())->merge(
            '<routes><route method="GET" name="/" action="A"/><route method="GET" name="/" action="B"/></routes>',
            BP . '/Core/Route/etc/routes.xsd'
        );
    }

    public function testSchemaValidationPasses(): void
    {
        $merger = new Merger();
        $merger->merge('<routes><route method="CLI" name="x" action="A"/></routes>', BP . '/Core/Route/etc/routes.xsd');

        $this->assertStringContainsString('method="CLI"', $merger->getContent());
    }

    public function testResultBeforeMergeIsAnError(): void
    {
        $this->expectException(\LogicException::class);
        (new Merger())->getDOMDocument();
    }

    public function testGetDomDocumentReturnsACopy(): void
    {
        $merger = new Merger();
        $merger->merge('<cfg><item name="x">1</item></cfg>');

        $merger->getDOMDocument()->documentElement->setAttribute('changed', '1');

        $this->assertStringNotContainsString('changed', $merger->getContent());
    }

    /**
     * Removes whitespace-only text nodes, so the comparison ignores formatting.
     */
    private function normalize(string $xml): string
    {
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->loadXML($xml);

        return (string) $document->saveXML();
    }
}

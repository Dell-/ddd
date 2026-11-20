<?php

declare(strict_types=1);

namespace Tests\Unit\Http\View;

use Codeception\Test\Unit;
use Core\Http\Exception\NotAcceptableException;
use Core\Http\Status;
use Core\Http\View\HtmlRenderer;
use Core\Http\View\JsonRenderer;
use Core\Http\View\TextRenderer;
use Core\Http\View\View;
use Core\Http\View\ViewRenderer;
use Core\Http\View\XmlEncoder;
use Core\Http\View\XmlRenderer;
use Tests\Support\Fixture\TemplateEngine;
use Tests\Support\Requests;

final class ViewTest extends Unit
{
    private ViewRenderer $renderer;

    protected function _before(): void
    {
        $this->renderer = new ViewRenderer([new JsonRenderer(), new XmlRenderer(), new HtmlRenderer(new TemplateEngine()), new TextRenderer()]);
    }

    public function testNegotiation(): void
    {
        $view = new View(['name' => 'A & B'], 'item', Status::Created, ['X-A' => '1']);

        $json = $this->render($view, null);
        $this->assertSame(['application/json', '{"name":"A & B"}', 201, '1', 'Accept'], [
            $json->getHeaderLine('Content-Type'), (string) $json->getBody(), $json->getStatusCode(), $json->getHeaderLine('X-A'), $json->getHeaderLine('Vary'),
        ]);
        $this->assertStringContainsString('<name>A &amp; B</name>', (string) $this->render($view, 'application/xml')->getBody());
        $this->assertSame('text/xml', $this->render($view, 'text/xml')->getHeaderLine('Content-Type'));
        $html = $this->render($view, 'text/html,application/xml;q=0.9');
        $this->assertSame(['text/html; charset=utf-8', '<h1>A &amp; B</h1>'], [$html->getHeaderLine('Content-Type'), (string) $html->getBody()]);
    }

    public function testHtmlNeedsATemplateOrHtmlData(): void
    {
        $this->assertSame(['application/json', 'application/xml', 'text/xml'], $this->renderer->getMediaTypes(new View(['a' => 1])));
        $this->assertSame(['application/json', 'application/xml', 'text/xml'], $this->renderer->getMediaTypes(new View(['a' => 1], 'missing-template')));
        $this->assertSame('<b>raw</b>', (string) $this->render(new View('<b>raw</b>'), 'text/html')->getBody());
        $this->assertSame('42', (string) $this->render(new View(42), 'text/plain')->getBody());
        $this->assertFalse(new HtmlRenderer()->supports(new View([], 'item')), 'no engine');
    }

    public function testNotAcceptable(): void
    {
        try {
            $this->render(new View(['a' => 1]), 'text/html');
            $this->fail('406 expected');
        } catch (NotAcceptableException $e) {
            $this->assertSame(['application/json', 'application/xml', 'text/xml'], $e->getAvailable());
        }
    }

    public function testVaryIsAppended(): void
    {
        $this->assertSame('Origin, Accept', $this->render(new View([], headers: ['Vary' => 'Origin']), null)->getHeaderLine('Vary'));
    }

    public function testContext(): void
    {
        $this->assertSame(['a' => 1], new View(['a' => 1])->context());
        $this->assertSame(['data' => [1, 2]], new View([1, 2])->context());
        $this->assertSame(['data' => 'x'], new View('x')->context());
        $this->assertSame(['p' => 1], new View(new class { public int $p = 1; })->context());
        $this->assertSame(201, new View()->withStatus(201)->status);
        $this->assertSame(['X' => '1'], new View()->withHeader('X', '1')->headers);
    }

    public function testXmlEncoder(): void
    {
        $xml = new XmlEncoder()->encode([
            'id' => 1,
            'tags' => ['a', 'b'],
            'active' => true,
            'note' => null,
            '1st key' => 'x',
            'status' => Status::Ok,
            'date' => new \DateTimeImmutable('2026-01-02T03:04:05+00:00'),
        ]);

        $this->assertStringContainsString(
            '<response><id>1</id><tags><item>a</item><item>b</item></tags><active>true</active>'
            . '<note xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/><_1st_key>x</_1st_key>'
            . '<status>200</status><date>2026-01-02T03:04:05+00:00</date></response>',
            $xml
        );
        $this->assertStringContainsString('<items/>', new XmlEncoder()->encode([], 'items'));
    }

    private function render(View $view, ?string $accept): \Psr\Http\Message\ResponseInterface
    {
        return $this->renderer->render($view, Requests::make('GET', '/', $accept === null ? [] : ['Accept' => $accept]));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Response;

use Codeception\Test\Unit;
use Core\Http\Response\EmptyResponse;
use Core\Http\Response\HtmlResponse;
use Core\Http\Response\JsonLinesResponse;
use Core\Http\Response\JsonResponse;
use Core\Http\Response\RedirectResponse;
use Core\Http\Response\TextResponse;
use Core\Http\Response\XmlResponse;
use Core\Http\Status;

final class ResponsesTest extends Unit
{
    public function testJson(): void
    {
        $response = new JsonResponse(['url' => '/a/б', 'n' => 1.0], Status::Accepted, ['X-A' => '1']);

        $this->assertSame(202, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertSame('1', $response->getHeaderLine('X-A'));
        $this->assertSame('{"url":"/a/б","n":1.0}', (string) $response->getBody());
        $this->assertSame('application/problem+json', new JsonResponse([], mediaType: 'application/problem+json')->getHeaderLine('Content-Type'));
        $this->expectException(\JsonException::class);
        new JsonResponse("\xB1\x31");
    }

    public function testJsonLines(): void
    {
        $generator = (static function (): \Generator {
            yield ['id' => 1];
            yield 'two';
        })();

        $lines = new JsonLinesResponse($generator);
        $this->assertSame('application/x-ndjson', $lines->getHeaderLine('Content-Type'));
        $this->assertSame("{\"id\":1}\n\"two\"\n", (string) $lines->getBody());

        $sequence = new JsonLinesResponse([1, [2]], sequence: true);
        $this->assertSame('application/json-seq', $sequence->getHeaderLine('Content-Type'));
        $this->assertSame("\x1E1\n\x1E[2]\n", (string) $sequence->getBody());
        $this->assertSame('application/jsonl', new JsonLinesResponse([], mediaType: 'application/jsonl')->getHeaderLine('Content-Type'));
    }

    public function testTextFormats(): void
    {
        $this->assertSame(['text/html; charset=utf-8', '<p>x</p>'], [new HtmlResponse('<p>x</p>')->getHeaderLine('Content-Type'), (string) new HtmlResponse('<p>x</p>')->getBody()]);
        $this->assertSame('text/plain; charset=utf-8', new TextResponse('x', 418)->getHeaderLine('Content-Type'));
        $this->assertSame(418, new TextResponse('x', 418)->getStatusCode());
    }

    public function testXml(): void
    {
        $dom = new \DOMDocument();
        $dom->loadXML('<a>1</a>');

        $this->assertSame('<b/>', (string) new XmlResponse('<b/>')->getBody());
        $this->assertStringContainsString('<a>1</a>', (string) new XmlResponse($dom)->getBody());
        $this->assertStringContainsString('<a>1</a>', (string) new XmlResponse(simplexml_load_string('<a>1</a>'))->getBody());
        $this->assertStringContainsString('<response><id>1</id></response>', (string) new XmlResponse(['id' => 1])->getBody());
        $this->assertSame('application/atom+xml', new XmlResponse('<feed/>', mediaType: 'application/atom+xml')->getHeaderLine('Content-Type'));
    }

    public function testEmptyAndRedirect(): void
    {
        $this->assertSame([204, '', []], [new EmptyResponse()->getStatusCode(), (string) new EmptyResponse()->getBody(), new EmptyResponse()->getHeaders()]);
        $this->assertSame(205, new EmptyResponse(Status::ResetContent)->getStatusCode());

        $redirect = new RedirectResponse('/new?a=1', Status::PermanentRedirect);
        $this->assertSame([308, '/new?a=1'], [$redirect->getStatusCode(), $redirect->getHeaderLine('Location')]);
        $this->assertSame(302, new RedirectResponse(new \Core\Http\Message\Uri('https://x.example/'))->getStatusCode());
        $this->expectException(\InvalidArgumentException::class);
        new RedirectResponse('/x', 200);
    }
}

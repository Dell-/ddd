<?php

declare(strict_types=1);

namespace Tests\Unit\Http\View;

use Codeception\Test\Unit;
use Core\Http\Exception\UnprocessableContentException;
use Core\Http\Status;
use Core\Http\View\ProblemDetails;
use Core\Http\View\ProblemRenderer;
use Tests\Support\Requests;

final class ProblemTest extends Unit
{
    public function testMembers(): void
    {
        $problem = new ProblemDetails(403, 'Forbidden', 'https://example.com/probs/credit', 'Balance too low', '/account/1', ['balance' => 30, 'status' => 'ignored', 'detail' => 'ignored']);

        $this->assertSame(
            ['type' => 'https://example.com/probs/credit', 'title' => 'Forbidden', 'status' => 403, 'detail' => 'Balance too low', 'instance' => '/account/1', 'balance' => 30],
            $problem->jsonSerialize()
        );
        $this->assertSame(['type' => 'about:blank', 'title' => 'Not Found', 'status' => 404], ProblemDetails::fromStatus(Status::NotFound)->jsonSerialize());
        $this->assertSame(
            ['type' => 'about:blank', 'title' => 'Unprocessable Content', 'status' => 422, 'detail' => 'The content contains invalid values.', 'errors' => ['a' => 'b']],
            ProblemDetails::fromException(new UnprocessableContentException(['a' => 'b']))->jsonSerialize()
        );
    }

    public function testNegotiatedFormats(): void
    {
        $problem = new ProblemDetails(404, 'Not Found', detail: 'No <item>', extensions: ['ids' => [1, 2]]);
        $renderer = new ProblemRenderer();
        $render = static fn (?string $accept) => $renderer->render($problem, Requests::make('GET', '/', $accept === null ? [] : ['Accept' => $accept]), ['Allow' => 'GET']);

        foreach ([[null, 'application/problem+json'], ['application/json', 'application/problem+json'], ['*/*', 'application/problem+json'],
            ['application/xml', 'application/problem+xml'], ['text/html', 'text/html; charset=utf-8'], ['text/plain', 'text/plain; charset=utf-8'],
            ['image/png', 'application/problem+json']] as [$accept, $type]) {
            $response = $render($accept);
            $this->assertSame($type, $response->getHeaderLine('Content-Type'), (string) $accept);
            $this->assertSame(404, $response->getStatusCode());
            $this->assertSame('GET', $response->getHeaderLine('Allow'));
            $this->assertSame('Accept', $response->getHeaderLine('Vary'));
        }

        $this->assertSame('{"type":"about:blank","title":"Not Found","status":404,"detail":"No <item>","ids":[1,2]}', (string) $render('application/json')->getBody());
        $this->assertStringContainsString(
            '<problem xmlns="urn:ietf:rfc:7807"><type>about:blank</type><title>Not Found</title><status>404</status><detail>No &lt;item&gt;</detail><ids><i>1</i><i>2</i></ids></problem>',
            (string) $render('application/xml')->getBody()
        );
        $this->assertStringContainsString('<h1>404 Not Found</h1><p>No &lt;item&gt;</p>', (string) $render('text/html')->getBody());
        $this->assertSame('No <item>', (string) $render('text/plain')->getBody());
    }
}

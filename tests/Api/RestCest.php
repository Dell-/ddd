<?php

declare(strict_types=1);

namespace Tests\Api;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Tests\Support\ApiTester;

/**
 * The REST resource /items of the test routes.
 */
final class RestCest
{
    public function _before(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/json');
    }

    public function listWithQueryDefaultsAndNextLink(ApiTester $I): void
    {
        $I->sendGet('/items', ['page' => 2]);

        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeHttpHeader('Vary', 'Accept');
        $I->seeResponseContainsJson(['page' => 2, 'limit' => 3, 'next' => '/items?page=3&limit=3', 'items' => [['id' => 4, 'name' => 'Item 4']]]);
    }

    public function show(ApiTester $I): void
    {
        $I->sendGet('/items/7');

        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals('{"id":7,"name":"Item 7"}');
    }

    public function notFoundIsProblemDetails(ApiTester $I): void
    {
        $I->sendGet('/items/101');

        $I->seeResponseCodeIs(404);
        $I->seeHttpHeader('Content-Type', 'application/problem+json');
        $I->seeResponseEquals('{"type":"about:blank","title":"Not Found","status":404,"detail":"Item 101 not found."}');
    }

    public function createWithLocation(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', ['name' => 'Новий']);

        $I->seeResponseCodeIs(201);
        $I->seeHttpHeader('Location', '/items/42');
        $I->seeResponseContainsJson(['id' => 42, 'name' => 'Новий']);
    }

    public function createFromForm(ApiTester $I): void
    {
        $I->sendPost('/items', ['name' => 'Form']);

        $I->seeResponseCodeIs(201);
        $I->seeResponseContainsJson(['name' => 'Form']);
    }

    public function validationErrors(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', ['title' => 'no name']);

        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['status' => 422, 'title' => 'Unprocessable Content', 'errors' => ['name' => 'The name is required.']]);
    }

    public function malformedJson(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', '{"name":');

        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['status' => 400, 'detail' => 'Invalid JSON body: Syntax error.']);
    }

    /**
     * @return list<array{method: string, type: string}>
     */
    protected function updateProvider(): array
    {
        return [
            ['method' => 'PUT', 'type' => 'application/json'],
            ['method' => 'PATCH', 'type' => 'application/merge-patch+json'],
            ['method' => 'PUT', 'type' => 'application/x-www-form-urlencoded'],
            ['method' => 'PATCH', 'type' => 'application/x-www-form-urlencoded'],
        ];
    }

    #[DataProvider('updateProvider')]
    public function update(ApiTester $I, Example $example): void
    {
        $I->haveHttpHeader('Content-Type', $example['type']);
        $I->send($example['method'], '/items/3', $example['type'] === 'application/x-www-form-urlencoded' ? 'name=Changed&tags[]=a' : '{"name":"Changed","tags":["a"]}');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['id' => 3, 'method' => $example['method'], 'data' => ['name' => 'Changed', 'tags' => ['a']]]);
    }

    public function deleteHasNoContent(ApiTester $I): void
    {
        $response = $I->executeInGuzzle(static fn (\GuzzleHttp\Client $client) => $client->request('DELETE', '/items/3', ['http_errors' => false]));

        $I->assertSame(204, $response->getStatusCode());
        $I->assertFalse($response->hasHeader('Content-Type'), 'PhpBrowser would report a default text/html');
        $I->assertSame('', (string) $response->getBody());
    }

    public function methodOverride(ApiTester $I): void
    {
        $I->sendPost('/items/3', ['_method' => 'PATCH', 'name' => 'Form']);
        $I->seeResponseContainsJson(['method' => 'PATCH', 'data' => ['name' => 'Form']]);

        // the first response set the CSRF cookie: like a browser, the client now has to send the token
        $I->haveHttpHeader('X-XSRF-TOKEN', (string) $I->grabCookie('XSRF-TOKEN'));
        $I->haveHttpHeader('X-HTTP-Method-Override', 'DELETE');
        $I->sendPost('/items/3');
        $I->seeResponseCodeIs(204);
    }
}

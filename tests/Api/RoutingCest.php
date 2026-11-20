<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

/**
 * Canonical URLs, declared query parameters, encoding and method semantics.
 */
final class RoutingCest
{
    public function _before(ApiTester $I): void
    {
        $I->stopFollowingRedirects();
        $I->haveHttpHeader('Accept', 'application/json');
    }

    public function trailingSlashRedirectsToTheCanonicalUrl(ApiTester $I): void
    {
        $I->sendGet('/items/?page=2');
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader('Location', '/items?page=2');

        $I->sendGet('/docs');
        $I->seeResponseCodeIs(301);
        $I->seeHttpHeader('Location', '/docs/');

        $I->sendGet('/docs/');
        $I->seeResponseCodeIs(200);
    }

    public function strictRoutesHaveOneForm(ApiTester $I): void
    {
        $I->sendGet('/strict');
        $I->seeResponseCodeIs(200);

        $I->sendGet('/strict/');
        $I->seeResponseCodeIs(404);
    }

    public function ignoreRoutesServeBothForms(ApiTester $I): void
    {
        foreach (['/lenient/5', '/lenient/5/'] as $path) {
            $I->sendGet($path);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['route' => 'lenient', 'params' => ['id' => '5']]);
        }
    }

    public function nonSafeMethodsKeepTheirMethod(ApiTester $I): void
    {
        $I->sendPut('/items/5/', '{}');

        $I->seeResponseCodeIs(308);
        $I->seeHttpHeader('Location', '/items/5');
    }

    public function followedRedirectReachesTheResource(ApiTester $I): void
    {
        $I->startFollowingRedirects();
        $I->sendGet('/items/7/');

        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['id' => 7]);
    }

    public function declaredQueryParametersAreValidated(ApiTester $I): void
    {
        $I->sendGet('/search');
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => ['q' => 'is required']]);

        $I->sendGet('/items', ['page' => 'abc']);
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['errors' => ['page' => 'must match "\d+"']]);

        $I->sendGet('/search?q=php&tags[]=a&tags[]=b');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['query' => ['q' => 'php', 'tags' => ['a', 'b']]]);
    }

    public function encodedPathParameters(ApiTester $I): void
    {
        $I->sendGet('/archive/2026/hello%20world%2Fagain');
        $I->seeResponseContainsJson(['route' => 'archive', 'params' => ['year' => '2026', 'slug' => 'hello world/again']]);

        $I->sendGet('/echo/a/b/c.txt');
        $I->seeResponseContainsJson(['params' => ['tail' => 'a/b/c.txt']]);

        $I->sendGet('/archive/26/x');
        $I->seeResponseCodeIs(404);
    }

    public function methodNotAllowed(ApiTester $I): void
    {
        $I->sendDelete('/items');
        $I->seeResponseCodeIs(405);
        $I->seeHttpHeader('Allow', 'GET, HEAD, POST, OPTIONS');

        $I->send('PROPFIND', '/items');
        $I->seeResponseCodeIs(405);
    }

    public function options(ApiTester $I): void
    {
        $I->sendOptions('/items/1');
        $I->seeResponseCodeIs(204);
        $I->seeHttpHeader('Allow', 'GET, HEAD, PUT, PATCH, DELETE, OPTIONS');

        $I->sendOptions('/custom-options');
        $I->seeHttpHeader('X-Route', 'options.explicit');

        $I->sendOptions('/nope');
        $I->seeResponseCodeIs(404);
    }

    public function head(ApiTester $I): void
    {
        $I->sendHead('/items/7');
        $I->seeResponseCodeIs(200);
        $I->seeHttpHeader('Content-Type', 'application/json');
        $I->seeResponseEquals('');

        $I->sendHead('/head');
        $I->seeHttpHeader('X-Route', 'head.explicit');
    }

    public function serverErrorsHideDetails(ApiTester $I): void
    {
        $I->sendGet('/fail');

        $I->seeResponseCodeIs(500);
        $I->seeResponseContainsJson(['status' => 500, 'title' => 'Internal Server Error']);
        $I->dontSeeResponseContains('Secret');
    }
}

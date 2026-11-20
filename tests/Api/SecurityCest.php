<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

/**
 * Security headers, CORS and CSRF over real HTTP.
 */
final class SecurityCest
{
    public function securityHeadersOnEveryResponse(ApiTester $I): void
    {
        foreach (['/items/1', '/nope'] as $path) {
            $I->sendGet($path);
            $I->seeHttpHeader('X-Content-Type-Options', 'nosniff');
            $I->seeHttpHeader('X-Frame-Options', 'DENY');
            $I->seeHttpHeader('Content-Security-Policy', "frame-ancestors 'none'");
            $I->seeHttpHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }
    }

    public function corsPreflight(ApiTester $I): void
    {
        $I->haveHttpHeader('Origin', 'https://app.example.com');
        $I->haveHttpHeader('Access-Control-Request-Method', 'PUT');
        $I->haveHttpHeader('Access-Control-Request-Headers', 'content-type, x-xsrf-token');
        $I->sendOptions('/items/1');

        $I->seeResponseCodeIs(204);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://app.example.com');
        $I->seeHttpHeader('Access-Control-Allow-Methods', 'GET, HEAD, POST, PUT, PATCH, DELETE');
        $I->seeHttpHeader('Access-Control-Allow-Headers', 'content-type, x-xsrf-token');
        $I->seeHttpHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function corsPreflightRejected(ApiTester $I): void
    {
        $I->haveHttpHeader('Origin', 'https://evil.example');
        $I->haveHttpHeader('Access-Control-Request-Method', 'DELETE');
        $I->sendOptions('/items/1');

        $I->seeResponseCodeIs(403);
        $I->dontSeeHttpHeader('Access-Control-Allow-Origin');
    }

    public function corsActualRequestsAndErrors(ApiTester $I): void
    {
        $I->haveHttpHeader('Origin', 'https://shop.partner.example');
        $I->sendGet('/items/1');
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://shop.partner.example');
        $I->seeHttpHeader('Access-Control-Expose-Headers', 'Location');
        $I->seeHttpHeader('Vary', 'Accept, Origin');

        $I->sendGet('/items/101');
        $I->seeResponseCodeIs(404);
        $I->seeHttpHeader('Access-Control-Allow-Origin', 'https://shop.partner.example');
    }

    public function csrfFlow(ApiTester $I): void
    {
        $I->sendGet('/items/1');
        $token = $I->grabCookie('XSRF-TOKEN');
        $I->assertIsString($token);
        $I->assertStringNotContainsString('HttpOnly', (string) $I->grabHttpHeader('Set-Cookie'));

        // the browser sends the cookie back, but a forged request cannot add the header
        $I->haveHttpHeader('Accept', 'application/json');
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', '{"name":"x"}');
        $I->seeResponseCodeIs(403);
        $I->seeResponseContainsJson(['detail' => 'Invalid or missing CSRF token.']);

        $I->haveHttpHeader('X-XSRF-TOKEN', $token);
        $I->sendPost('/items', '{"name":"x"}');
        $I->seeResponseCodeIs(201);

        $I->haveHttpHeader('Origin', 'https://evil.example');
        $I->sendPost('/items', '{"name":"x"}');
        $I->seeResponseCodeIs(403);
    }

    public function csrfFormField(ApiTester $I): void
    {
        $I->sendGet('/');
        $token = (string) $I->grabCookie('XSRF-TOKEN');

        $I->sendPost('/items', ['name' => 'Form', '_csrf' => $token]);
        $I->seeResponseCodeIs(201);
    }

    public function csrfExemptions(ApiTester $I): void
    {
        // a token API client has no cookies
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/items', '{"name":"x"}');
        $I->seeResponseCodeIs(201);

        // webhooks are excepted even with cookies
        $I->setCookie('session', 'x');
        $I->sendPost('/webhooks/payment', '{"paid":true}');
        $I->seeResponseCodeIs(200);
    }
}

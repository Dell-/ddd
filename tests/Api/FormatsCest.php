<?php

declare(strict_types=1);

namespace Tests\Api;

use Tests\Support\ApiTester;

/**
 * Content negotiation of responses and errors, JSON Lines / JSON Text Sequences, XML bodies.
 */
final class FormatsCest
{
    public function itemInEveryFormat(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/xml');
        $I->sendGet('/items/7');
        $I->seeHttpHeader('Content-Type', 'application/xml');
        $I->seeResponseContains('<response><id>7</id><name>Item 7</name></response>');

        $I->haveHttpHeader('Accept', 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8');
        $I->sendGet('/items/7');
        $I->seeHttpHeader('Content-Type', 'text/html; charset=utf-8');
        $I->seeResponseEquals('<h1>Item 7</h1>');

        $I->haveHttpHeader('Accept', '*/*');
        $I->sendGet('/items/7');
        $I->seeHttpHeader('Content-Type', 'application/json');
    }

    public function templateEscaping(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/html');
        $I->sendGet('/view/template');

        $I->seeResponseEquals('<h1>Templated &lt;item&gt;</h1>');
    }

    public function notAcceptable(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'text/html');
        $I->sendGet('/view');

        $I->seeResponseCodeIs(406);
        $I->seeHttpHeader('Content-Type', 'text/html; charset=utf-8');
        $I->seeResponseContains('406 Not Acceptable');
        $I->seeResponseContains('Available media types: application/json, application/xml, text/xml.');
    }

    public function dataAsXml(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/xml');
        $I->sendGet('/view');

        $I->seeResponseContains('<response><id>1</id><tags><item>a</item><item>b</item></tags><active>true</active><note xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:nil="true"/></response>');
    }

    public function problemFormats(ApiTester $I): void
    {
        $I->haveHttpHeader('Accept', 'application/xml');
        $I->sendGet('/nope');
        $I->seeHttpHeader('Content-Type', 'application/problem+xml');
        $I->seeResponseContains('<problem xmlns="urn:ietf:rfc:7807">');

        $I->haveHttpHeader('Accept', 'text/plain');
        $I->sendGet('/nope');
        $I->seeResponseEquals('No route for "GET /nope".');
    }

    public function jsonLinesResponses(ApiTester $I): void
    {
        $I->sendGet('/records');
        $I->seeHttpHeader('Content-Type', 'application/x-ndjson');
        $I->seeResponseEquals("{\"id\":1}\n{\"id\":2}\n{\"id\":3}\n");

        $I->sendGet('/records?seq=1');
        $I->seeHttpHeader('Content-Type', 'application/json-seq');
        $I->seeResponseEquals("\x1E{\"id\":1}\n\x1E{\"id\":2}\n\x1E{\"id\":3}\n");
    }

    public function multiLineJsonRequests(ApiTester $I): void
    {
        foreach (['application/x-ndjson' => "{\"a\":1}\n{\"a\":2}\n\n[3]\n", 'application/jsonl' => "1\r\n2\r\n", 'application/json-seq' => "\x1E{\"a\":1}\n\x1E{\"a\":2}\n"] as $type => $body) {
            $I->resetCookie('XSRF-TOKEN'); // a cookie-less API client (CSRF is checked only with cookies)
            $I->haveHttpHeader('Content-Type', $type);
            $I->sendPost('/records', $body);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['count' => $type === 'application/x-ndjson' ? 3 : 2]);
        }

        $I->resetCookie('XSRF-TOKEN');
        $I->haveHttpHeader('Content-Type', 'application/x-ndjson');
        $I->haveHttpHeader('Accept', 'application/json');
        $I->sendPost('/records', "{}\n{bad\n");
        $I->seeResponseCodeIs(400);
        $I->seeResponseContainsJson(['detail' => 'Invalid JSON on line 2: Syntax error.']);
    }

    public function xmlRequests(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/xml');
        $I->sendPut('/echo', '<item><name>From XML</name></item>');
        $I->seeResponseContainsJson(['body' => ['xml' => 'item', 'name' => 'From XML']]);

        $I->sendPut('/echo', '<!DOCTYPE x [<!ENTITY e SYSTEM "file:///etc/passwd">]><x>&e;</x>');
        $I->seeResponseCodeIs(400);
    }

    public function vendorJsonTypes(ApiTester $I): void
    {
        $I->haveHttpHeader('Content-Type', 'application/vnd.api+json; charset=utf-8');
        $I->sendPatch('/echo', '{"data":{"type":"items"}}');

        $I->seeResponseContainsJson(['body' => ['data' => ['type' => 'items']]]);
    }
}

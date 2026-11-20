<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Codeception\Test\Unit;
use Core\Http\Method;
use Core\Http\Status;

final class StatusTest extends Unit
{
    public function testReasonPhrases(): void
    {
        $this->assertSame('Not Found', Status::NotFound->reasonPhrase());
        $this->assertSame('Content Too Large', Status::ContentTooLarge->reasonPhrase());
        $this->assertSame('Unprocessable Content', Status::UnprocessableContent->reasonPhrase());
        $this->assertSame('OK', Status::reasonPhraseOf(200));
        $this->assertSame('', Status::reasonPhraseOf(299), 'unregistered codes have no phrase');
        $this->assertSame(404, Status::code(Status::NotFound));
        $this->assertSame(299, Status::code(299));
    }

    public function testEveryCaseHasAPhrase(): void
    {
        foreach (Status::cases() as $status) {
            $this->assertNotSame('', $status->reasonPhrase(), $status->name);
        }
        $this->assertGreaterThanOrEqual(60, count(Status::cases()));
    }

    public function testClasses(): void
    {
        $this->assertTrue(Status::Continue->isInformational());
        $this->assertTrue(Status::Created->isSuccessful());
        $this->assertTrue(Status::PermanentRedirect->isRedirection());
        $this->assertTrue(Status::Gone->isClientError());
        $this->assertTrue(Status::BadGateway->isServerError());
        $this->assertFalse(Status::Ok->isClientError());
    }

    public function testWithoutContent(): void
    {
        foreach ([100, 101, 204, 304, Status::NoContent] as $status) {
            $this->assertTrue(Status::isWithoutContent($status));
        }
        foreach ([200, 205, 404] as $status) {
            $this->assertFalse(Status::isWithoutContent($status));
        }
    }

    public function testMethods(): void
    {
        $this->assertSame(Method::Patch, Method::fromName('patch'));
        $this->assertNull(Method::fromName('PROPFIND'));
        $this->assertSame(['GET', 'HEAD', 'OPTIONS', 'TRACE'], array_values(array_map(
            static fn (Method $m): string => $m->value,
            array_filter(Method::cases(), static fn (Method $m): bool => $m->isSafe())
        )));
        $this->assertTrue(Method::Put->isIdempotent());
        $this->assertTrue(Method::Delete->isIdempotent());
        $this->assertFalse(Method::Post->isIdempotent());
        $this->assertFalse(Method::Patch->isIdempotent());
        $this->assertTrue(Method::Head->isCacheable());
        $this->assertFalse(Method::Post->isCacheable());
    }
}

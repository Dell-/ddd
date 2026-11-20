<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Exception;

use Codeception\Test\Unit;
use Core\Http\Exception\BadRequestException;
use Core\Http\Exception\ClientErrorException;
use Core\Http\Exception\HttpException;
use Core\Http\Exception\MethodNotAllowedException;
use Core\Http\Exception\NotAcceptableException;
use Core\Http\Exception\NotFoundException;
use Core\Http\Exception\ProxyAuthenticationRequiredException;
use Core\Http\Exception\RangeNotSatisfiableException;
use Core\Http\Exception\ServerErrorException;
use Core\Http\Exception\ServiceUnavailableException;
use Core\Http\Exception\TooManyRequestsException;
use Core\Http\Exception\UnauthorizedException;
use Core\Http\Exception\UnprocessableContentException;
use Core\Http\Exception\UnsupportedMediaTypeException;
use Core\Http\Exception\UpgradeRequiredException;
use Core\Http\Status;

final class HttpExceptionTest extends Unit
{
    public function testProblemMembers(): void
    {
        $e = new HttpException(Status::Conflict, 'Version mismatch', ['X-A' => 'b'], null, 'https://example.com/probs/version', 'Version conflict', ['current' => 3]);

        $this->assertSame(409, $e->getStatusCode());
        $this->assertSame(409, $e->getCode());
        $this->assertSame('Version mismatch', $e->getMessage());
        $this->assertSame('Version mismatch', $e->getDetail());
        $this->assertSame('Version conflict', $e->getTitle());
        $this->assertSame('https://example.com/probs/version', $e->getType());
        $this->assertSame(['current' => 3], $e->getExtensions());
        $this->assertSame(['X-A' => ['b']], $e->getHeaders());
    }

    public function testDefaults(): void
    {
        $e = new NotFoundException();

        $this->assertSame('about:blank', $e->getType());
        $this->assertSame('Not Found', $e->getTitle());
        $this->assertNull($e->getDetail());
        $this->assertSame('Not Found', $e->getMessage());
        $this->assertInstanceOf(ClientErrorException::class, $e);
        $this->assertInstanceOf(ServerErrorException::class, new ServiceUnavailableException());
    }

    public function testOnlyErrorStatuses(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpException(302);
    }

    public function testRequiredHeaders(): void
    {
        $this->assertSame(['WWW-Authenticate' => ['Bearer realm="api"']], new UnauthorizedException('Bearer realm="api"')->getHeaders());
        $this->assertSame(['Proxy-Authenticate' => ['Basic']], new ProxyAuthenticationRequiredException()->getHeaders());
        $this->assertSame(['Allow' => ['GET, HEAD']], new MethodNotAllowedException(['GET', 'HEAD'])->getHeaders());
        $this->assertSame(['Content-Range' => ['bytes */100']], new RangeNotSatisfiableException(100)->getHeaders());
        $this->assertSame(['Accept' => ['application/json']], new UnsupportedMediaTypeException(['application/json'])->getHeaders());
        $this->assertSame(['Retry-After' => ['120']], new TooManyRequestsException(120)->getHeaders());
        $this->assertSame(
            ['Retry-After' => ['Wed, 21 Oct 2026 07:28:00 GMT']],
            new ServiceUnavailableException(new \DateTimeImmutable('2026-10-21 07:28:00 UTC'))->getHeaders()
        );
        $this->assertSame(['Upgrade' => ['HTTP/2'], 'Connection' => ['Upgrade']], new UpgradeRequiredException('HTTP/2')->getHeaders());
    }

    public function testValidationErrorsAreProblemExtensions(): void
    {
        $e = new UnprocessableContentException(['name' => 'is required']);

        $this->assertSame(422, $e->getStatusCode());
        $this->assertSame(['errors' => ['name' => 'is required']], $e->getExtensions());
        $this->assertSame(['name' => 'is required'], $e->getErrors());
        $this->assertSame(['available' => ['application/json']], new NotAcceptableException(['application/json'])->getExtensions());
    }

    public function testEveryExceptionClassHasItsStatus(): void
    {
        $directory = BP . '/Core/Http/Exception';
        $count = 0;
        foreach (glob($directory . '/*Exception.php') ?: [] as $file) {
            $class = 'Core\\Http\\Exception\\' . basename($file, '.php');
            $reflection = new \ReflectionClass($class);
            $constructor = $reflection->getConstructor();
            if ($reflection->isAbstract() || $constructor === null || $constructor->getNumberOfRequiredParameters() > 0) {
                continue;
            }
            $exception = $reflection->newInstance();
            $this->assertInstanceOf(HttpException::class, $exception);
            $status = Status::from($exception->getStatusCode());
            $this->assertSame($status->reasonPhrase(), $exception->getTitle(), $class);
            $this->assertStringStartsWith(str_replace(' ', '', ucwords(strtolower($status->name === 'UriTooLong' ? 'Uri Too Long' : preg_replace('/(?<!^)[A-Z]/', ' $0', $status->name)))), basename($file, '.php'));
            $count++;
        }
        $this->assertGreaterThanOrEqual(30, $count);
        $this->assertInstanceOf(BadRequestException::class, new BadRequestException('x'));
    }
}

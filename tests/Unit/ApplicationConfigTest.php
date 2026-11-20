<?php

declare(strict_types=1);

namespace Tests\Unit;

use Application\Action\Cli\Hello;
use Application\Action\Http\Index;
use Application\Service;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Core\Console\Application;
use Core\Http\Kernel;
use Core\Route\Router;
use Tests\Support\TestApplication;

/**
 * The real application configuration (app/Application/etc) and the test configuration.
 */
final class ApplicationConfigTest extends Unit
{
    /**
     * @return iterable<array{string, string}>
     */
    public static function configProvider(): iterable
    {
        foreach ([BP . '/Application/etc', TestApplication::configDirectory()] as $directory) {
            yield [$directory . '/di.xml', 'Di/etc/di.xsd'];
            yield [$directory . '/routes.xml', 'Route/etc/routes.xsd'];
        }
    }

    #[DataProvider('configProvider')]
    public function testConfigIsValid(string $file, string $schema): void
    {
        $document = new \DOMDocument();
        $document->load($file);

        $this->assertTrue($document->schemaValidate(BP . '/Core/' . $schema));
    }

    public function testApplicationIsWired(): void
    {
        putenv('APP_SECRET=app-config-test-secret-0123456789abcdef');
        try {
            $container = \Bootstrap::createContainer();
            $this->assertInstanceOf(Service::class, \Bootstrap::createApplication());
            $this->assertInstanceOf(Kernel::class, $container->get(Kernel::class));
            $this->assertInstanceOf(Application::class, $container->get(Application::class));

            $router = $container->get(Router::class);
            \assert($router instanceof Router);
            $this->assertSame(Index::class, $router->match('GET', '/')->route->action);
            $this->assertSame(Hello::class, $router->matchCommand('hello'));
        } finally {
            putenv('APP_SECRET');
        }
    }

    public function testSecretIsRequired(): void
    {
        putenv('APP_SECRET');
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Environment variable APP_SECRET is not set.');
        \Bootstrap::createContainer()->get(Kernel::class);
    }
}

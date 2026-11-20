<?php

declare(strict_types=1);

namespace Tests\Unit;

use Application\Action\Cli\Hello;
use Application\Action\Http\Index;
use Application\Service;
use Codeception\Test\Unit;
use Core\Route\Config as RouteConfig;
use Codeception\Attribute\DataProvider;

/**
 * The real application configuration (app/Application/etc).
 */
final class ApplicationConfigTest extends Unit
{
    /**
     * @return iterable<array{string, string}>
     */
    public static function configProvider(): iterable
    {
        yield ['di.xml', 'Di/etc/di.xsd'];
        yield ['routes.xml', 'Route/etc/routes.xsd'];
    }

    #[DataProvider('configProvider')]
    public function testConfigIsValid(string $file, string $schema): void
    {
        $document = new \DOMDocument();
        $document->load(BP . '/Application/etc/' . $file);

        $this->assertTrue($document->schemaValidate(BP . '/Core/' . $schema));
    }

    public function testApplicationIsCreated(): void
    {
        $this->assertInstanceOf(Service::class, \Bootstrap::createApplication());
    }

    public function testRoutes(): void
    {
        $container = $this->createAppContainer();
        $routes = $container->get(RouteConfig::class);

        $this->assertSame(Index::class, $routes->match('GET', '/'));
        $this->assertSame(Hello::class, $routes->match('CLI', 'hello'));
    }

    private function createAppContainer(): \Core\Di\ContainerInterface
    {
        $service = \Bootstrap::createApplication();
        $property = new \ReflectionProperty($service, 'handlerFactory');
        $handlerFactory = $property->getValue($service);

        return (new \ReflectionProperty($handlerFactory, 'container'))->getValue($handlerFactory);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Di;

use Codeception\Test\Unit;
use Core\Di\Config;
use Core\Di\Config\Argument\Service as ArgumentService;
use Core\Di\Config\Argument\TypeFactory;
use Core\Di\Config\Xml\Converter;
use Core\Di\Container;
use Core\Di\ContainerInterface;
use Tests\Support\Fixture\Dependency;
use Tests\Support\Fixture\DependencyInterface;
use Tests\Support\Fixture\Leaf;
use Tests\Support\Fixture\Service;
use Tests\Support\Fixture\ServiceInterface;
use Tests\Support\Fixture\Unresolvable;
use Tests\Support\StringReader;

final class ContainerTest extends Unit
{
    private const NS = 'Tests\Support\Fixture\\';

    public function testContainerReturnsItself(): void
    {
        // An interface without instance config after one with it used to overwrite the Container definition
        $container = $this->createContainer(
            '<interface name="Core\Di\ContainerInterface" class="Core\Di\Container"/>'
            . '<interface name="' . self::NS . 'DependencyInterface" class="' . self::NS . 'Dependency"/>'
            . '<instance class="Core\Di\Container" shared="true"/>'
        );

        $this->assertSame($container, $container->get(Container::class));
        $this->assertSame($container, $container->get(ContainerInterface::class));
        $this->assertInstanceOf(Dependency::class, $container->get(DependencyInterface::class));
    }

    public function testDependenciesAreAutowiredAndShared(): void
    {
        $container = $this->createContainer(
            '<interface name="' . self::NS . 'DependencyInterface" class="' . self::NS . 'Dependency"/>'
        );

        $service = $container->get(Service::class);

        $this->assertInstanceOf(Dependency::class, $service->dependency);
        $this->assertSame(0, $service->dependency->leaf->number);
        $this->assertSame($service, $container->get(Service::class));
        $this->assertSame($service->dependency, $container->get(DependencyInterface::class));
    }

    public function testCreateReturnsNewInstances(): void
    {
        $container = $this->createContainer();

        $this->assertNotSame($container->create(Leaf::class), $container->create(Leaf::class));
    }

    public function testArgumentsOfAllTypes(): void
    {
        $container = $this->createContainer(
            '<interface name="' . self::NS . 'ServiceInterface" class="' . self::NS . 'Service"/>'
            . '<instance class="' . self::NS . 'Service">'
            . '  <argument name="dependency"><object class="' . self::NS . 'Dependency">'
            . '    <argument name="leaf"><object class="' . self::NS . 'Leaf">'
            . '      <argument name="number"><int>7</int></argument>'
            . '    </object></argument>'
            . '  </object></argument>'
            . '  <argument name="list"><array>'
            . '    <item name="bool"><bool>true</bool></item>'
            . '    <item><float>1.5</float></item>'
            . '    <item name="nested"><array><item><string>x</string></item></array></item>'
            . '  </array></argument>'
            . '  <argument name="text"><string> keep spaces </string></argument>'
            . '  <argument name="path"><path base="BP">/some/file.log</path></argument>'
            . '</instance>'
        );

        $service = $container->create(ServiceInterface::class);

        $this->assertInstanceOf(Service::class, $service);
        $this->assertSame(7, $service->dependency->leaf->number);
        $this->assertSame(['bool' => true, 0 => 1.5, 'nested' => ['x']], $service->list);
        $this->assertSame(' keep spaces ', $service->text);
        $this->assertSame(BP . DS . 'some/file.log', $service->path);
    }

    public function testExplicitArgumentsOverrideConfiguredOnes(): void
    {
        $container = $this->createContainer(
            '<instance class="' . self::NS . 'Leaf"><argument name="number"><int>1</int></argument></instance>'
        );

        $this->assertSame(1, $container->create(Leaf::class)->number);
        $this->assertSame(2, $container->create(Leaf::class, ['number' => 2])->number);
    }

    public function testExplicitNullArgumentIsPassed(): void
    {
        $container = $this->createContainer(
            '<interface name="' . self::NS . 'DependencyInterface" class="' . self::NS . 'Dependency"/>'
        );

        $this->assertNull($container->create(Service::class, ['nullable' => null])->nullable);
        $this->assertSame('default', $container->create(Service::class)->nullable);
    }

    public function testSharedInstanceCreatedViaInterfaceIsReused(): void
    {
        $container = $this->createContainer(
            '<interface name="' . self::NS . 'DependencyInterface" class="' . self::NS . 'Dependency"/>'
            . '<instance class="' . self::NS . 'Dependency" shared="true"/>'
        );

        $created = $container->create(DependencyInterface::class);

        $this->assertSame($created, $container->get(DependencyInterface::class));
        $this->assertSame($created, $container->get(Dependency::class));
    }

    public function testCallbacksAreCalledInOrder(): void
    {
        $container = $this->createContainer(
            '<interface name="' . self::NS . 'DependencyInterface" class="' . self::NS . 'Dependency"/>'
            . '<instance class="' . self::NS . 'Service">'
            . '  <callback method="add"><argument name="name"><string>a</string></argument></callback>'
            . '  <callback method="add">'
            . '    <argument name="name"><string>b</string></argument>'
            . '    <argument name="value"><int>2</int></argument>'
            . '  </callback>'
            . '</instance>'
        );

        $this->assertSame([['a', 1], ['b', 2]], $container->create(Service::class)->calls);
    }

    public function testCallMethodAutowiresArguments(): void
    {
        $container = $this->createContainer();
        $service = new Service(new Dependency(new Leaf()));

        $this->assertSame($container->get(Leaf::class), $container->callMethod($service, 'withLeaf'));
    }

    public function testCallMethodRejectsMissingAndPrivateMethods(): void
    {
        $container = $this->createContainer();
        $service = new Service(new Dependency(new Leaf()));

        foreach (['missing' => 'does not exist', 'hidden' => 'must be public'] as $method => $message) {
            try {
                $container->callMethod($service, $method);
                $this->fail("$method() must be rejected");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    public function testUnresolvableArgumentIsReported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot resolve argument "$required"');
        $this->createContainer()->create(Unresolvable::class);
    }

    public function testInterfaceWithoutPreferenceIsReported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no preference is configured');
        $this->createContainer()->get(DependencyInterface::class);
    }

    public function testUnknownArgumentTypeIsReported(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined type (date)');
        $this->createContainer(
            '<instance class="' . self::NS . 'Leaf"><argument name="number"><date>1</date></argument></instance>'
        );
    }

    private function createContainer(string $di = ''): Container
    {
        return new Container(new Config(
            new StringReader('<di>' . $di . '</di>'),
            new Converter(new ArgumentService(new TypeFactory()))
        ));
    }
}

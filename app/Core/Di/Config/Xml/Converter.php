<?php

declare(strict_types=1);

namespace Core\Di\Config\Xml;

use Core\Config\ConverterInterface;
use Core\Config\Xml\DomToArray;
use Core\Di\Config\Argument\Service;
use Core\Di\InstanceClass;

/**
 * Converts di.xml into a map of InstanceClass keyed by class or interface name.
 */
readonly class Converter implements ConverterInterface
{
    public const string INTERFACE_ITEM = 'interface';
    public const string INSTANCE_ITEM = 'instance';
    public const string ARGUMENT_ITEM = 'argument';
    public const string CALLBACK_ITEM = 'callback';
    public const string ATTRIBUTES = DomToArray::ATTRIBUTES;
    public const string VALUE = DomToArray::VALUE;

    public function __construct(
        private Service $service,
        private DomToArray $domToArray = new DomToArray(),
    ) {
    }

    /**
     * @return array<string, InstanceClass>
     */
    #[\Override]
    public function convert(\DOMDocument $document): array
    {
        $data = $this->domToArray->convert($document);

        $result = $this->createInstances($data[self::INSTANCE_ITEM] ?? []);

        return $this->createPreferences($data[self::INTERFACE_ITEM] ?? [], $result);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, InstanceClass>
     */
    private function createInstances(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $class = trim($item[self::ATTRIBUTES]['class'], '\\');
            $instance = new InstanceClass(
                $class,
                $class,
                $this->service->createArguments($item[self::ARGUMENT_ITEM] ?? []),
                ($item[self::ATTRIBUTES]['shared'] ?? 'false') === 'true'
            );

            foreach ($item[self::CALLBACK_ITEM] ?? [] as $callback) {
                $instance->addCallback(
                    trim($callback[self::ATTRIBUTES]['method']),
                    $this->service->createArguments($callback[self::ARGUMENT_ITEM] ?? [])
                );
            }

            $result[$instance->getId()] = $instance;
        }

        return $result;
    }

    /**
     * An interface preference points to the instance definition of its class (if there is one).
     *
     * @param list<array<string, mixed>> $items
     * @param array<string, InstanceClass> $result
     * @return array<string, InstanceClass>
     */
    private function createPreferences(array $items, array $result): array
    {
        foreach ($items as $item) {
            $class = trim($item[self::ATTRIBUTES]['class'], '\\');
            $interface = trim($item[self::ATTRIBUTES]['name'], '\\');

            $result[$interface] = $result[$class] ?? new InstanceClass($interface, $class);
        }

        return $result;
    }
}

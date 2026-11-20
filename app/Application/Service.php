<?php

declare(strict_types=1);

namespace Application;

use Core\Console\Application as ConsoleApplication;
use Core\Console\Input;
use Core\Di\ContainerInterface;
use Core\Http\Emitter;
use Core\Http\Kernel;
use Core\Http\Message\ServerRequestFactory;
use Core\Service\ServiceInterface;

/**
 * Entry point: the console application in the CLI, the HTTP kernel otherwise.
 */
final class Service implements ServiceInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    #[\Override]
    public function run(): int
    {
        if (PHP_SAPI === 'cli') {
            $console = $this->container->get(ConsoleApplication::class);
            \assert($console instanceof ConsoleApplication);

            return $console->run(Input::fromArgv($_SERVER['argv'] ?? []));
        }

        $kernel = $this->container->get(Kernel::class);
        $emitter = $this->container->get(Emitter::class);
        \assert($kernel instanceof Kernel && $emitter instanceof Emitter);

        $request = ServerRequestFactory::fromGlobals();
        $response = $kernel->handle($request);
        $emitter->emit($response, $request->getMethod() !== 'HEAD');

        return $response->getStatusCode() < 400 ? 0 : 1;
    }
}

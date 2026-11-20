<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Di\ContainerInterface;
use Core\Http\Exception\NotFoundException;
use Core\Route\Router;
use Psr\Log\LoggerInterface;

/**
 * Runs the console command named by the first argument.
 */
final readonly class Application
{
    public function __construct(
        private Router $router,
        private ContainerInterface $container,
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function run(Input $input, Output $output = new Output()): int
    {
        try {
            $class = $this->router->matchCommand($input->command);
            $command = $this->container->create($class);
            if (!$command instanceof CommandInterface) {
                throw new \LogicException(sprintf('%s must implement %s.', $class, CommandInterface::class));
            }

            return $command->run($input, $output);
        } catch (NotFoundException $e) {
            $output->error($e->getMessage());

            return CommandInterface::FAILURE;
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
            $output->error($this->debug ? (string) $e : $e::class . ': ' . $e->getMessage());

            return CommandInterface::FAILURE;
        }
    }
}

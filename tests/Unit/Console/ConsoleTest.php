<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Codeception\Test\Unit;
use Core\Console\Application;
use Core\Console\Input;
use Core\Console\Output;
use Tests\Support\TestApplication;

final class ConsoleTest extends Unit
{
    public function testInput(): void
    {
        $input = Input::fromArgv(['bin/console', 'deploy', 'prod', '--force', '--tag=v1=final', '-v', '--', '--literal']);

        $this->assertSame('deploy', $input->command);
        $this->assertSame(['prod', '-v', '--literal'], $input->arguments);
        $this->assertSame(['force' => true, 'tag' => 'v1=final'], $input->options);
        $this->assertSame('prod', $input->getArgument(0));
        $this->assertSame('d', $input->getArgument(5, 'd'));
        $this->assertTrue($input->hasOption('force'));
        $this->assertSame('x', $input->getOption('missing', 'x'));
        $this->assertSame('', Input::fromArgv(['bin/console'])->command);
    }

    public function testApplication(): void
    {
        [$code, $stdout, $stderr] = $this->runCommand(['echo', 'a', 'b', '--name=X']);
        $this->assertSame([0, "echo:X:a,b\n", ''], [$code, $stdout, $stderr]);

        [$code, , $stderr] = $this->runCommand(['nope']);
        $this->assertSame([1, "Command \"nope\" not found.\n"], [$code, $stderr]);

        [$code, , $stderr] = $this->runCommand(['fail']);
        $this->assertSame([1, "RuntimeException: Command failed\n"], [$code, $stderr]);
    }

    public function testBinConsoleProcess(): void
    {
        $process = proc_open(
            [PHP_BINARY, BP . '/../bin/console', 'hello', '--name=Test'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['APP_ETC_DIR' => TestApplication::configDirectory()] + getenv()
        );
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        $this->assertSame([0, "Hello, Test!\n", ''], [proc_close($process), $stdout, $stderr]);
    }

    /**
     * @param list<string> $arguments
     * @return array{int, string, string}
     */
    private function runCommand(array $arguments): array
    {
        $stdout = fopen('php://memory', 'w+');
        $stderr = fopen('php://memory', 'w+');
        $application = TestApplication::createContainer()->get(Application::class);
        \assert($application instanceof Application && is_resource($stdout) && is_resource($stderr));

        $code = $application->run(Input::fromArgv(['bin/console', ...$arguments]), new Output($stdout, $stderr));
        rewind($stdout);
        rewind($stderr);

        return [$code, (string) stream_get_contents($stdout), (string) stream_get_contents($stderr)];
    }
}

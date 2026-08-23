<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Unit;

use Quillstack\Cli\CommandProviderInterface;
use Quillstack\Cli\Console;
use Quillstack\Cli\ConsoleKernel;
use Quillstack\Cli\Input;
use Quillstack\Cli\Tests\Mocks\BrokenCommand;
use Quillstack\Cli\Tests\Mocks\CommandProvider;
use Quillstack\Cli\Tests\Mocks\GreetCommand;
use Quillstack\DI\Container;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

class TestConsoleKernel
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array{code: int, output: string}
     */
    private function run(array $argv, array $config = [], bool $describe = false, array $extra = []): array
    {
        $console = new Console(
            new Container($config),
            $extra,
            new Output(new Colors(), false),
            $describe
        );

        ob_start();
        $code = $console->run($argv);

        return ['code' => $code, 'output' => (string) ob_get_clean()];
    }

    /**
     * A command is a class, and what it needs it asks for — the container builds it.
     */
    public function whatACommandNeedsIsBuiltForIt()
    {
        $result = $this->run(['quill', 'greet', 'ada'], [
            CommandProviderInterface::class => CommandProvider::class,
        ]);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'Hello, ada'));
    }

    /**
     * Typing nothing lists what there is, and the list is what the provider gave plus what
     * comes with the package.
     */
    public function nothingTypedListsWhatThereIs()
    {
        $result = $this->run(['quill'], [
            CommandProviderInterface::class => CommandProvider::class,
        ]);

        $this->assertBoolean->isTrue(str_contains($result['output'], 'greet'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'Says hello to somebody'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'list'));
    }

    /**
     * Without a provider there is still a list, so a tool with no commands of its own works.
     */
    public function withoutAProviderThereIsStillAList()
    {
        $result = $this->run(['quill']);

        $this->assertEqual->equal(0, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'list'));
    }

    /**
     * Commands added on top of the provider — the ones a framework brings itself, which may
     * or may not apply.
     */
    public function commandsCanBeAddedOnTop()
    {
        $result = $this->run(['quill'], [], false, [GreetCommand::class]);

        $this->assertBoolean->isTrue(str_contains($result['output'], 'greet'));
    }

    public function aCommandNobodyKnowsSaysSo()
    {
        $result = $this->run(['quill', 'nonsense']);

        $this->assertEqual->equal(1, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'no command called `nonsense`'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'Run list'));
    }

    /**
     * The exit code is the command's, so a shell can act on it.
     */
    public function theExitCodeIsTheCommandsOwn()
    {
        $result = $this->run(['quill', 'greet', 'ada', '--fail'], [
            CommandProviderInterface::class => CommandProvider::class,
        ]);

        $this->assertEqual->equal(3, $result['code']);
    }

    /**
     * Anything thrown is reported rather than reaching the terminal as a fatal error.
     */
    public function whatThrowsIsReported()
    {
        $result = $this->run(['quill', 'broken'], [], false, [BrokenCommand::class]);

        $this->assertEqual->equal(1, $result['code']);
        $this->assertBoolean->isTrue(str_contains($result['output'], 'as promised'));
        $this->assertBoolean->isFalse(str_contains($result['output'], 'RuntimeException'));
    }

    /**
     * Where failures are described, the exception is described too — which is for developing
     * and not for a server.
     */
    public function aFailureCanBeDescribed()
    {
        $result = $this->run(['quill', 'broken'], [], true, [BrokenCommand::class]);

        $this->assertBoolean->isTrue(str_contains($result['output'], 'as promised'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'RuntimeException'));
        $this->assertBoolean->isTrue(str_contains($result['output'], 'BrokenCommand.php'));
    }

    /**
     * The list is in order, so it reads the same way twice.
     */
    public function theListIsInOrder()
    {
        $kernel = new ConsoleKernel(
            new Container([CommandProviderInterface::class => CommandProvider::class]),
            new Output(new Colors(), false)
        );

        $this->assertEqual->equal(['greet', 'list'], array_keys($kernel->getCommands()));
    }

    /**
     * The kernel runs an input built by hand as readily as one read from a command line.
     */
    public function anInputBuiltByHandRunsToo()
    {
        $kernel = (new ConsoleKernel(new Container(), new Output(new Colors(), false)))
            ->add(GreetCommand::class);

        ob_start();
        $code = $kernel->run(new Input('greet', ['grace']));
        $output = (string) ob_get_clean();

        $this->assertEqual->equal(0, $code);
        $this->assertBoolean->isTrue(str_contains($output, 'Hello, grace'));
    }
}

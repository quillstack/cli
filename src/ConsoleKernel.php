<?php

declare(strict_types=1);

namespace Quillstack\Cli;

use Psr\Container\ContainerInterface;
use Quillstack\Cli\Commands\ListCommand;
use Quillstack\Cli\Exceptions\CommandNotFoundException;
use Quillstack\Output\OutputInterface;
use Throwable;

/**
 * Runs one command and answers with its exit code.
 *
 * A command is a class, and what it needs it asks for in the constructor — the container
 * builds it. Nothing here knows what any particular command is about.
 */
class ConsoleKernel
{
    /**
     * Command classes added on top of whatever the provider lists.
     *
     * @var array<int, class-string<CommandInterface>>
     */
    private array $extra = [];

    /**
     * @param bool $describeFailures whether an unexpected exception is described as well as
     *                               reported — useful while developing, not on a server
     */
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly OutputInterface $output,
        private readonly bool $describeFailures = false
    ) {
        //
    }

    /**
     * Adds commands which are not on the provider — the ones a framework brings itself, and
     * which may or may not apply.
     *
     * @param class-string<CommandInterface> ...$classes
     */
    public function add(string ...$classes): self
    {
        foreach ($classes as $class) {
            $this->extra[] = $class;
        }

        return $this;
    }

    public function run(Input $input): int
    {
        try {
            return $this->find($input->getCommand())->run($input, $this->output);
        } catch (CommandNotFoundException $exception) {
            $this->output->writeln("<red>{$exception->getMessage()}</red>");
            $this->output->writeln('Run <yellow>list</yellow> to see what there is.');

            return 1;
        } catch (Throwable $throwable) {
            return $this->reportFailure($throwable);
        }
    }

    /**
     * Every command there is, keyed by the name each is typed as.
     *
     * @return array<string, CommandInterface>
     */
    public function getCommands(): array
    {
        // Built here rather than through the container: it is handed this kernel, and a
        // container asked for one would build another which knows none of these commands.
        $list = new ListCommand($this);
        $byName = [$list->getName() => $list];

        /** @var array<int, class-string<CommandInterface>> $classes */
        $classes = [...$this->extra, ...$this->fromProvider()];

        foreach ($classes as $class) {
            /** @var CommandInterface $command */
            $command = $this->container->get($class);
            $byName[$command->getName()] = $command;
        }

        ksort($byName);

        return $byName;
    }

    /**
     * @return array<int, class-string<CommandInterface>>
     */
    private function fromProvider(): array
    {
        if (!$this->container->has(CommandProviderInterface::class)) {
            return [];
        }

        /** @var CommandProviderInterface $provider */
        $provider = $this->container->get(CommandProviderInterface::class);

        return $provider->getCommands();
    }

    private function find(string $name): CommandInterface
    {
        $commands = $this->getCommands();

        if (!isset($commands[$name])) {
            throw new CommandNotFoundException("There is no command called `{$name}`");
        }

        return $commands[$name];
    }

    /**
     * Says what went wrong. Where failures are described, the exception is described too —
     * on a server that is something a person typing should not be shown.
     */
    private function reportFailure(Throwable $throwable): int
    {
        $this->output->writeln("<red>{$throwable->getMessage()}</red>");

        if ($this->describeFailures) {
            $class = $throwable::class;
            $where = $throwable->getFile() . ':' . $throwable->getLine();

            $this->output->writeln("<dark-grey>{$class} in {$where}</dark-grey>");
            $this->output->writeln("<dark-grey>{$throwable->getTraceAsString()}</dark-grey>");
        }

        return 1;
    }
}

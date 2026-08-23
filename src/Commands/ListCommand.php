<?php

declare(strict_types=1);

namespace Quillstack\Cli\Commands;

use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\ConsoleKernel;
use Quillstack\Cli\Input;
use Quillstack\Output\OutputInterface;

/**
 * What runs when nothing was asked for: everything there is to ask for.
 *
 * It is handed the kernel it belongs to rather than asking the container for one. Asking
 * would build a second kernel — one which knows nothing of the commands added to the first,
 * and which needs everything the first was given.
 */
class ListCommand implements CommandInterface
{
    public function __construct(private readonly ConsoleKernel $kernel)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'list';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Lists the commands there are';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $commands = $this->kernel->getCommands();
        $width = max(1, ...array_map('strlen', array_keys($commands)));

        $output->writeln('<green>Commands</green>');

        foreach ($commands as $name => $command) {
            $padded = str_pad($name, $width);
            $output->writeln("  <yellow>{$padded}</yellow>  {$command->getDescription()}");
        }

        return 0;
    }
}

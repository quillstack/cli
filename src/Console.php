<?php

declare(strict_types=1);

namespace Quillstack\Cli;

use Psr\Container\ContainerInterface;
use Quillstack\Output\Colors;
use Quillstack\Output\Output;
use Quillstack\Output\OutputInterface;

/**
 * A command line, for a tool which has no framework behind it.
 *
 * Where there is one — quillstack/framework — it builds the kernel itself, with its own
 * container and its own commands. This is the short way in for everything else.
 */
class Console
{
    private readonly ConsoleKernel $kernel;

    /**
     * @param array<int, class-string<CommandInterface>> $commands
     */
    public function __construct(
        ContainerInterface $container,
        array $commands = [],
        ?OutputInterface $output = null,
        bool $describeFailures = false
    ) {
        $this->kernel = new ConsoleKernel(
            $container,
            $output ?? new Output(new Colors(), self::isTerminal()),
            $describeFailures
        );
        $this->kernel->add(...$commands);
    }

    /**
     * @param string[] $argv
     */
    public function run(array $argv): int
    {
        return $this->kernel->run(Input::fromArgv($argv));
    }

    public function kernel(): ConsoleKernel
    {
        return $this->kernel;
    }

    /**
     * Escape codes belong on a terminal and nowhere else, so output piped into a file is the
     * text alone.
     */
    private static function isTerminal(): bool
    {
        return defined('STDOUT') && function_exists('stream_isatty') && @stream_isatty(STDOUT);
    }
}

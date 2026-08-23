<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Mocks;

use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\Input;
use Quillstack\Output\OutputInterface;

/**
 * A command which asks for something in its constructor, to show that it is built rather
 * than assembled.
 */
class GreetCommand implements CommandInterface
{
    public function __construct(private readonly Greeting $greeting)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'greet';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Says hello to somebody';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        $name = $input->getArgument(0, 'world');
        $output->writeln($this->greeting->to((string) $name));

        return $input->hasOption('fail') ? 3 : 0;
    }
}

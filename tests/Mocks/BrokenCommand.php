<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Mocks;

use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\Input;
use Quillstack\Output\OutputInterface;
use RuntimeException;

class BrokenCommand implements CommandInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'broken';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Throws, on purpose';
    }

    /**
     * {@inheritDoc}
     */
    public function run(Input $input, OutputInterface $output): int
    {
        throw new RuntimeException('as promised');
    }
}

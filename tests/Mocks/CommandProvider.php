<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Mocks;

use Quillstack\Cli\CommandProviderInterface;

class CommandProvider implements CommandProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCommands(): array
    {
        return [GreetCommand::class];
    }
}

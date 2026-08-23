<?php

declare(strict_types=1);

namespace Quillstack\Cli;

interface CommandProviderInterface
{
    /**
     * The commands of the application, given as class names.
     *
     * @return array<int, class-string<CommandInterface>>
     */
    public function getCommands(): array;
}

<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Mocks;

class Greeting
{
    public function to(string $name): string
    {
        return "Hello, {$name}";
    }
}

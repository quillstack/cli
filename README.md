# Quillstack Cli

A command line kernel: a command is a class, and what it needs it asks for. Full
documentation: https://quillstack.org/cli

No annotations, no definitions, no builder. A class says what it is called, what it does, and
what happens when it runs — and the container builds it with whatever it asked for in its
constructor.

### Requirements

- PHP 8.1 or newer
- A PSR-11 container, to build the commands

### Installation

```shell
composer require quillstack/cli
```

### Usage

#### A command

```php
use Quillstack\Cli\CommandInterface;
use Quillstack\Cli\Input;
use Quillstack\Output\OutputInterface;

final class GreetCommand implements CommandInterface
{
    public function __construct(private readonly Greeting $greeting)
    {
    }

    public function getName(): string
    {
        return 'greet';
    }

    public function getDescription(): string
    {
        return 'Says hello to somebody';
    }

    public function run(Input $input, OutputInterface $output): int
    {
        $output->writeln($this->greeting->to((string) $input->getArgument(0, 'world')));

        return 0;
    }
}
```

`Greeting` is built for it, the same way everything else is.

#### Saying which commands there are

```php
use Quillstack\Cli\CommandProviderInterface;

final class CommandProvider implements CommandProviderInterface
{
    public function getCommands(): array
    {
        return [GreetCommand::class];
    }
}
```

#### Running

```php
#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Quillstack\Cli\Console;
use Quillstack\Cli\CommandProviderInterface;
use Quillstack\DI\Container;

$console = new Console(new Container([
    CommandProviderInterface::class => CommandProvider::class,
]));

exit($console->run($argv));
```

```shell
$ ./bin/tool greet ada
Hello, ada

$ ./bin/tool
Commands
  greet  Says hello to somebody
  list   Lists the commands there are
```

Typing nothing lists what there is.

### What was typed

```shell
./bin/tool queue:work emails --sleep=5 --keep-running -v
```

```php
$input->getCommand();              // 'queue:work'
$input->getArgument(0);            // 'emails'
$input->getArgument(1, 'none');    // 'none'
$input->getOption('sleep');        // '5'
$input->getOption('keep-running'); // true
$input->hasOption('v');            // true
```

`--name=value` carries one, `--name` and `-n` are the fact that they were written, and `-abc`
is three of them. Only the first `=` separates, so `--dsn=mysql:host=localhost;dbname=shop`
arrives whole. Options may be written before the arguments, after them, or on both sides.

### Failures

Anything a command throws is reported rather than reaching the terminal as a fatal error, and
the exit code is `1`. Where failures are described — while developing, not on a server — the
exception, where it came from, and the trace are shown as well:

```php
new Console($container, describeFailures: true);
```

A command nobody knows says so, and says how to find out what there is.

### Technical documentation

| Class | What it is |
| --- | --- |
| `Console` | the short way in: builds the kernel and runs `$argv` |
| `ConsoleKernel` | finds the command, runs it, turns anything thrown into something readable |
| `Input` | what was typed, taken apart |
| `CommandInterface` | `getName()`, `getDescription()`, `run()` |
| `CommandProviderInterface` | `getCommands(): array` — the classes |
| `Commands\ListCommand` | comes with the package |
| `Exceptions\CliException` | what everything here extends |

`ConsoleKernel::add()` puts commands on top of whatever the provider lists, which is how
[quillstack/framework](https://github.com/quillstack/framework) adds the ones that only apply
where the application configured something — `db:migrate` where there are entities,
`queue:work` where there is a queue.

The list command is built by the kernel rather than through the container, because a kernel
asked for from a container would be a second one, knowing none of the commands added to the
first.

### Unit tests

```shell
composer test
composer test:coverage
composer stan
```

### License

MIT. See [LICENSE](LICENSE).

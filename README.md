# Quillstack Cli

[![Tests](https://github.com/quillstack/cli/actions/workflows/tests.yml/badge.svg)](https://github.com/quillstack/cli/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/quillstack/cli.svg)](https://packagist.org/packages/quillstack/cli)
[![Downloads](https://img.shields.io/packagist/dt/quillstack/cli.svg)](https://packagist.org/packages/quillstack/cli)
[![PHP Version](https://img.shields.io/packagist/php-v/quillstack/cli)](https://packagist.org/packages/quillstack/cli)
[![StyleCI](https://github.styleci.io/repos/1343640677/shield?branch=main)](https://github.styleci.io/repos/1343640677?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/quillstack/cli/badge)](https://www.codefactor.io/repository/github/quillstack/cli)
[![Quality Gate](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cli&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=quillstack_cli)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cli&metric=coverage)](https://sonarcloud.io/summary/new_code?id=quillstack_cli)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cli&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cli)
[![Reliability](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cli&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cli)
[![Security](https://sonarcloud.io/api/project_badges/measure?project=quillstack_cli&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=quillstack_cli)
[![License](https://img.shields.io/packagist/l/quillstack/cli)](https://github.com/quillstack/cli/blob/main/LICENSE)

A command line kernel: a command is a class, and what it needs it asks for. Full
documentation: https://quillstack.org/cli

## Why this exists

No annotations, no definitions, no builder. A class says what it is called, what it does, and
what happens when it runs — and the container builds it with whatever it asked for in its
constructor.

Console libraries tend to ask you to describe a command twice: once as a class, and once as a
definition of its name, arguments and options — in a constructor call, an attribute, or a
configuration array. The two drift, and the definition is what runs. Here there is one
description, because the class is the definition, and what a command needs it takes in its
constructor like anything else in the framework.

## Requirements

- PHP 8.1 or newer
- A PSR-11 container, to build the commands

## Installation

```shell
composer require quillstack/cli
```

## Usage

### A command

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

### Saying which commands there are

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

### Running

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

## What was typed

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

## Failures

Anything a command throws is reported rather than reaching the terminal as a fatal error, and
the exit code is `1`. Where failures are described — while developing, not on a server — the
exception, where it came from, and the trace are shown as well:

```php
new Console($container, describeFailures: true);
```

A command nobody knows says so, and says how to find out what there is.

## Technical documentation

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

## Benchmark

Against `symfony/console` 7.4.17 and `minicli` 4.2.1, on PHP 8.4 on an M-series Mac.

The number a user actually feels is how long the whole command takes, so start there. 60 runs
of the same `greet ada`, interleaved, median of five:

| | Whole process | Of which is the library |
| --- | --- | --- |
| bare `php` on an empty file | 44.22 ms | — |
| **quillstack/cli** | **46.61 ms** | **2.4 ms** |
| minicli | 46.92 ms | 2.7 ms |
| symfony/console | 51.92 ms | 7.7 ms |

**Read that column on the left first: about 95% of the wait is PHP starting up, and no console
library can do anything about it.** Choosing between these three moves a command by a few
milliseconds against a fixed 44 that you pay regardless.

That said, the part which is the library differs by more than the whole-process figures let you
see, because most of those milliseconds are spent reading code off disk once. Measuring
bootstrap and dispatch inside a single process, where the loading is amortised away, leaves what
it costs to *run*:

| Package | Version | Bootstrap + dispatch |
| --- | --- | --- |
| **quillstack/cli** | 0.6.0 | **5.0 µs** |
| minicli | 4.2.1 | 12.5 µs |
| symfony/console | 7.4.17 | 78.5 µs |

And what has to be loaded to get there, which does not vary by machine:

| Package | Files loaded | Memory | On disk |
| --- | --- | --- | --- |
| **quillstack/cli** | **30** | **104 KB** | 108 KB |
| minicli | 36 | 220 KB | 352 KB |
| symfony/console | 39 | 774 KB | 952 KB |

The reason is not that this is written better. `symfony/console` is doing considerably more:
typed options and arguments with validation, output formatting and styles, progress bars,
tables, interactive questions, shell completion, and command discovery. If you want any of
those, that 78.5 µs buys them, and this package will not do them for you. What is measured
here is the cost of the part all three share — deciding which command was asked for and
running it — which is the only part this package has.

## Tests

```shell
composer test
composer test:coverage
```

### Static analysis

```shell
composer stan
```

## The rest of Quillstack

This is one component of [Quillstack](https://github.com/quillstack), a PHP framework which is
as simple to use as it is strict about what it does.

- [quillstack/di](https://github.com/quillstack/di) — what builds the commands
- [quillstack/output](https://github.com/quillstack/output) — where a command writes
- [quillstack/framework](https://github.com/quillstack/framework) — the same idea for HTTP
- [quillstack/benchmark](https://github.com/quillstack/benchmark) — commands worth timing

## License

MIT — see [LICENSE](https://github.com/quillstack/cli/blob/main/LICENSE).

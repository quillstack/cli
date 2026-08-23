<?php

declare(strict_types=1);

namespace Quillstack\Cli\Tests\Unit;

use Quillstack\Cli\Input;
use Quillstack\UnitTests\AssertEqual;
use Quillstack\UnitTests\Types\AssertBoolean;

/**
 * What was typed, taken apart.
 */
class TestInput
{
    public function __construct(
        private AssertEqual $assertEqual,
        private AssertBoolean $assertBoolean
    ) {
        //
    }

    public function theFirstThingIsTheCommand()
    {
        $input = Input::fromArgv(['quill', 'queue:work']);

        $this->assertEqual->equal('queue:work', $input->getCommand());
        $this->assertEqual->equal([], $input->getArguments());
    }

    /**
     * Nothing typed at all lists what there is, which is the friendliest thing to do.
     */
    public function nothingTypedListsWhatThereIs()
    {
        $this->assertEqual->equal('list', Input::fromArgv(['quill'])->getCommand());
    }

    public function whateverFollowsAreArguments()
    {
        $input = Input::fromArgv(['quill', 'greet', 'ada', 'grace']);

        $this->assertEqual->equal(['ada', 'grace'], $input->getArguments());
        $this->assertEqual->equal('ada', $input->getArgument(0));
        $this->assertEqual->equal('grace', $input->getArgument(1));
        $this->assertEqual->equal('nobody', $input->getArgument(2, 'nobody'));
    }

    public function anOptionWithAValue()
    {
        $input = Input::fromArgv(['quill', 'queue:work', '--sleep=5']);

        $this->assertEqual->equal('5', $input->getOption('sleep'));
        $this->assertBoolean->isTrue($input->hasOption('sleep'));
    }

    /**
     * An option without a value is the fact that it was written.
     */
    public function anOptionWithoutAValueIsTrue()
    {
        $input = Input::fromArgv(['quill', 'queue:work', '--keep-running']);

        $this->assertBoolean->isTrue($input->getOption('keep-running') === true);
        $this->assertBoolean->isTrue($input->hasOption('keep-running'));
    }

    /**
     * `-abc` is three of them, the way every command line has always read it.
     */
    public function shortFlagsCanBeWrittenTogether()
    {
        $input = Input::fromArgv(['quill', 'run', '-abc']);

        foreach (['a', 'b', 'c'] as $flag) {
            $this->assertBoolean->isTrue($input->hasOption($flag));
        }

        $this->assertEqual->equal(['a' => true, 'b' => true, 'c' => true], $input->getOptions());
    }

    /**
     * An option may hold something with an `=` in it, so only the first one separates.
     */
    public function aValueMayHoldTheSeparator()
    {
        $input = Input::fromArgv(['quill', 'db:migrate', '--dsn=mysql:host=localhost;dbname=shop']);

        $this->assertEqual->equal('mysql:host=localhost;dbname=shop', $input->getOption('dsn'));
    }

    /**
     * Options may come before the arguments, after them, or on both sides.
     */
    public function optionsAndArgumentsInAnyOrder()
    {
        $input = Input::fromArgv(['quill', 'greet', '--loud', 'ada', '--times=3', 'grace']);

        $this->assertEqual->equal('greet', $input->getCommand());
        $this->assertEqual->equal(['ada', 'grace'], $input->getArguments());
        $this->assertEqual->equal('3', $input->getOption('times'));
        $this->assertBoolean->isTrue($input->hasOption('loud'));
    }

    /**
     * An option written before the command still leaves the command where it is.
     */
    public function anOptionBeforeTheCommand()
    {
        $input = Input::fromArgv(['quill', '--verbose', 'greet']);

        $this->assertEqual->equal('greet', $input->getCommand());
        $this->assertBoolean->isTrue($input->hasOption('verbose'));
    }

    public function whatWasNotTypedAnswersWithTheDefault()
    {
        $input = Input::fromArgv(['quill', 'greet']);

        $this->assertEqual->equal('1', $input->getOption('times', '1'));
        $this->assertBoolean->isFalse($input->hasOption('times'));
        $this->assertBoolean->isTrue($input->getOption('nothing') === null);
    }

    /**
     * A single `-` is a thing in its own right on a command line, and not a flag with no name.
     */
    public function aLoneDashIsAnArgument()
    {
        $input = Input::fromArgv(['quill', 'read', '-']);

        $this->assertEqual->equal(['-'], $input->getArguments());
        $this->assertEqual->equal([], $input->getOptions());
    }

    /**
     * Built by hand rather than read from a command line, for a test or a script.
     */
    public function oneCanBeBuiltByHand()
    {
        $input = new Input('greet', ['ada'], ['loud' => true]);

        $this->assertEqual->equal('greet', $input->getCommand());
        $this->assertEqual->equal('ada', $input->getArgument(0));
        $this->assertBoolean->isTrue($input->hasOption('loud'));
    }
}

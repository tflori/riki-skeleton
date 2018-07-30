<?php

namespace Test\Unit\Cli;

use App\Cli\CliKernel;
use GetOpt\ArgumentException\Missing;
use GetOpt\Command;
use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;
use Hugga\Console;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;
use Mockery as m;

class KernelTest extends TestCase
{
    /** @var CliKernel */
    protected $kernel;

    protected function setUp()
    {
        parent::setUp();
        $this->kernel = new CliKernel();
        $this->kernel->loadCommands($this->app);
    }

    protected function initDependencies()
    {
        parent::initDependencies();

        $this->mocks['getOpt'] = m::mock(GetOpt::class)->makePartial();
        $this->app->instance(GetOpt::class, $this->mocks['getOpt']);
    }

    /** @test */
    public function definesAPlainTextHandler()
    {
        $result = $this->kernel->getErrorHandlers($this->app);

        self::assertInstanceOf(PlainTextHandler::class, $result[0]);
    }

    public function provideDefaultOptions()
    {
        return [
            ['h', 'help'],
            ['v', 'verbose'],
            ['q', 'quiet'],
        ];
    }

    /** @dataProvider provideDefaultOptions
     * @param string $short
     * @param string $long
     * @test */
    public function registersDefaultOptions($short, $long)
    {
        /** @var GetOpt|m\Mock $getOpt */
        $getOpt = $this->mocks['getOpt'];

        $this->kernel->loadCommands($this->app);

        self::assertSame($short, $getOpt->getOptionObject($long)->getShort());
    }

    /** @test */
    public function showsHelpWhenRequested()
    {
        $this->mocks['getOpt']->shouldReceive('getHelpText')->with()
            ->once()->andReturn('GetOpts help text');

        $this->mocks['console']->shouldReceive('write')->with('GetOpts help text')
            ->once();
        $this->mocks['console']->shouldNotReceive('error');

        $this->kernel->handle($this->app, '--help');
    }

    /** @test */
    public function showsAnErrorWhenCommandIsMissing()
    {
        $this->mocks['console']->shouldReceive('error')->with('No command given')
            ->once();

        $this->kernel->handle($this->app, '');
    }

    /** @test */
    public function catchesMissingExceptionWhenHelpRequested()
    {
        $this->mocks['getOpt']->addCommand(
            Command::create('something', 'empty')
                ->addOperand(Operand::create('foo', Operand::REQUIRED))
        );

        $this->mocks['console']->shouldNotReceive('error');

        $this->kernel->handle($this->app, 'something --help');
    }

    /** @test */
    public function showsGetOptsErrorMessage()
    {
        $this->mocks['getOpt']->addCommand(
            Command::create('something', 'empty')
                ->addOperand(Operand::create('foo', Operand::REQUIRED))
        );
        $this->mocks['getOpt']->shouldReceive('getHelpText')->with()
            ->once()->andReturn('GetOpts help text');

        $this->mocks['console']->shouldReceive('write')->with('GetOpts help text')->once();
        $this->mocks['console']->shouldReceive('error')->with('Operand foo is required')->once();

        $returnVar = $this->kernel->handle($this->app, 'something');

        self::assertSame(128, $returnVar);
    }

    /** @test */
    public function increasesVerbosityLevel()
    {
        $this->mocks['getOpt']->addCommand(
            Command::create('foo', function(GetOpt $getOpt) {
                return 0;
            })
        );

        $this->mocks['console']->shouldReceive('increaseVerbosity')->with()->times(3);

        $this->kernel->handle($this->app, 'foo -vvv');
    }

    /** @test */
    public function setsVerbosityLevelHigh()
    {
        $this->mocks['getOpt']->addCommand(
            Command::create('foo', function(GetOpt $getOpt) {
                return 0;
            })
        );

        $this->mocks['console']->shouldReceive('setVerbosity')->with(Console::WEIGHT_HIGH)->once();

        $this->kernel->handle($this->app, 'foo -q');
    }
}

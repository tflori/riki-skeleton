<?php

namespace Test\Cli;

use App\Cli\CliKernel;
use GetOpt\GetOpt;
use Hugga\Console;
use Mockery as m;

class TestCase extends \Test\TestCase
{
    protected function start(array $arguments)
    {
        $kernel = new CliKernel();
        $this->bootstrap(...$kernel->getBootstrappers());

        $outFile = '/tmp/test.stdout';
        $errFile = '/tmp/test.stderr';
        $stdout = fopen($outFile, 'w');
        $stderr = fopen($errFile, 'w');
        $this->mocks['console']->setStdout($stdout);
        $this->mocks['console']->setStderr($stderr);

        $returnVar = $this->app->run($kernel, $arguments);

        fclose($stdout);
        fclose($stderr);

        return [
            'returnVar' => $returnVar,
            'output' => file_get_contents($outFile),
            'errors' => file_get_contents($errFile)
        ];
    }

    protected function initDependencies()
    {
        parent::initDependencies();

        $this->mocks['console'] = m::mock(Console::class, [])->makePartial();
        $this->mocks['console']->disableAnsi();
//        $this->mocks['console']->shouldNotReceive(['ask', 'getLine']);
        $this->app->instance('console', $this->mocks['console']);

        $this->mocks['getOpt'] = m::mock(GetOpt::class)->makePartial();
        $this->app->instance(GetOpt::class, $this->mocks['getOpt']);
    }
}

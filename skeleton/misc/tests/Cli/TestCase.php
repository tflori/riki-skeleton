<?php

namespace Test\Cli;

use App\Cli\Kernel;

class TestCase extends \Test\TestCase
{
    protected function start(array $arguments)
    {
        $kernel = new Kernel();
        $this->bootstrap(...$kernel->getBootstrappers());
        ob_start();
        $returnVar = $kernel->handle($arguments);
        return ['returnVar' => $returnVar, 'output' => ob_get_clean()];
    }

    protected function bootstrap(callable ...$bootstrappers)
    {
        foreach ($bootstrappers as $bootstrapper) {
            call_user_func($bootstrapper, $this->app);
        }
    }
}

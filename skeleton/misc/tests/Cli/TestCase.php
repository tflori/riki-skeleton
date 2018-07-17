<?php

namespace Test\Cli;

use App\Cli\Kernel;

class TestCase extends \Test\TestCase
{
    protected function start(array $arguments)
    {
        $kernel = new Kernel();
        ob_start();
        $returnVar = $this->app->run($kernel, $arguments);
        return ['returnVar' => $returnVar, 'output' => ob_get_clean()];
    }
}

<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use Whoops\Handler\PlainTextHandler;

class CliKernel extends \App\Kernel
{
    public function __construct()
    {
        // $this->addBootstrappers();
    }

    public function handle(\Riki\Application $app, $arguments = null): int
    {
        if (!$arguments) {
            // @todo change this to your needs
            // During tests we don't create arguments from super globals
            // @codeCoverageIgnoreStart
            $arguments = $_SERVER['argv'];
            // @codeCoverageIgnoreEnd
        }

        // @todo route the request to your command
        echo 'Command not found' . PHP_EOL;
        return 0;
    }

    public function getErrorHandlers(Application $app): array
    {
        return [new PlainTextHandler()];
    }
}

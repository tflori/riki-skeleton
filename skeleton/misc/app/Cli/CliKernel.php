<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use App\Kernel;
use Whoops\Handler\PlainTextHandler;

class CliKernel extends Kernel
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
        // bootstrap the kernel
    }

    public function handle($arguments = null): int
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

    public function getErrorHandlers(): array
    {
        return [new PlainTextHandler()];
    }
}

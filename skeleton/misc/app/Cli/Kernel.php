<?php

namespace App\Cli;

use App\Application;
use App\Cli\Command;
use Whoops\Handler\PlainTextHandler;

class Kernel extends \Riki\Kernel
{
    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'initWhoops']
        );
    }

    public function handle($arguments = null): int
    {
        if (!$arguments) {
            // @todo change this to your needs
            // During tests we don't create a arguments from super globals
            // @codeCoverageIgnoreStart
            $arguments = $_SERVER['argv'];
            // @codeCoverageIgnoreEnd
        }

        // @todo route the request to your command
        echo 'Command not found' . PHP_EOL;
        return 0;
    }

    public function initWhoops(Application $app): bool
    {
        $app->appendWhoopsHandler(new PlainTextHandler());
        return true;
    }
}

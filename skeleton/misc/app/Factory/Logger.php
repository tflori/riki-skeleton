<?php

namespace App\Factory;

use App\DI;
use DependencyInjector\Factory\AbstractFactory;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

class Logger extends AbstractFactory
{
    protected $shared = true;

    /**
     * This method builds the instance.
     *
     * @return \Monolog\Logger
     * @codeCoverageIgnore Logger does not get created in tests
     * @throws \Exception
     */
    protected function build()
    {
        $logPath = DI::environment()->logPath('/words-backend.log');
        $handler = new StreamHandler($logPath, DI::config()->logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true));
        return new \Monolog\Logger('app', [ $handler ]);
    }
}

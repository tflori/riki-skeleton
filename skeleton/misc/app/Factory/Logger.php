<?php

namespace App\Factory;

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
        $logPath = $this->container->get('environment')->logPath('/riki.log');
        $handler = new StreamHandler($logPath, $this->container->get('config')->logLevel);
        $handler->setFormatter(new LineFormatter(null, null, true));
        return new \Monolog\Logger('app', [ $handler ]);
    }
}

<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;
use Hugga\Console;

class ConsoleFactory extends AbstractFactory
{
    protected $shared = true;

    /**
     * @return Console
     * @codeCoverageIgnore ConsoleFactory get's mocked in tests
     */
    protected function build()
    {
        return new Console($this->container->get('logger'));
    }
}

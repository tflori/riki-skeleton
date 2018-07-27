<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;

class Console extends AbstractFactory
{
    protected $shared = true;

    /**
     * @return \Hugga\Console
     * @codeCoverageIgnore Console get's mocked in tests
     */
    protected function build()
    {
        return new \Hugga\Console($this->container->get('logger'));
    }
}

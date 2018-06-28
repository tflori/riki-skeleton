<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;

class Console extends AbstractFactory
{
    protected $shared = true;

    protected function build()
    {
        return new \Hugga\Console($this->container->get('logger'));
    }
}

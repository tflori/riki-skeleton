<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;

class Environment extends AbstractFactory
{
    protected $shared = true;

    /**
     * This method builds the instance.
     *
     * @return mixed
     */
    protected function build()
    {
        return Environment::init('App\\Environment');
    }
}

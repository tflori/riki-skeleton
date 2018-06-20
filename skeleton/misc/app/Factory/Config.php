<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;

class Config extends AbstractFactory
{
    protected $shared = true;

    /**
     * This method builds the instance.
     *
     * @return mixed
     */
    protected function build()
    {
        return \App\Config::restoreOrCreate($this->container->get('environment'));
    }
}

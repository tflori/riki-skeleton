<?php

namespace App\Factory;

use DependencyInjector\Factory\AbstractFactory;
use Http\HttpResponse;

class Response extends AbstractFactory
{
    /**
     * This method builds the instance.
     *
     * @return mixed
     */
    protected function build()
    {
        return new HttpResponse();
    }
}

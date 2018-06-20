<?php

namespace App;

use GetOpt\Arguments;

abstract class Kernel
{
    /** @var callable[] */
    protected $bootstrappers = [];

    abstract public function handle();

    public function addBootstrappers(callable ...$bootstrapper)
    {
        array_push($this->bootstrappers, ...$bootstrapper);
        return $this;
    }

    public function getBootstrappers()
    {
        return $this->bootstrappers;
    }
}

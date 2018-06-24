<?php

namespace App\Cli\Command\Config;

use App\Cli\AbstractCommand;
use GetOpt\GetOpt;

class Cache extends AbstractCommand
{
    protected $name = 'config:cache';

    protected $description = 'Create a configuration cache when caching is enabled for this environment.';

    public function handle(GetOpt $getOpt): int
    {
        var_dump($getOpt->getOperands());
        return 0;
    }
}

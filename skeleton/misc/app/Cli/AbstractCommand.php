<?php

namespace App\Cli;

use GetOpt\Command;
use GetOpt\GetOpt;

abstract class AbstractCommand extends Command
{
    /** @var string */
    protected $name = 'unnamed';

    /** @var string */
    protected $description = '';

    public function __construct()
    {
        parent::__construct($this->name, [$this, 'handle']);
        if (!empty($this->description)) {
            $this->setDescription($this->description);
        }
    }

    abstract public function handle(GetOpt $getOpt): int;
}

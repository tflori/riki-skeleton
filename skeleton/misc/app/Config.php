<?php

namespace App;

use Monolog\Logger;
use Riki\Environment;

class Config extends \Riki\Config
{
    public $logLevel = Logger::WARNING;

    public function __construct(Environment $environment)
    {
        parent::__construct($environment);
        $this->logLevel = $this->env('LOG_LEVEL', $this->logLevel);
    }
}

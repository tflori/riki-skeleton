<?php

namespace App;

abstract class Kernel extends \Riki\Kernel
{
    /** @var Application */
    protected $app;

    abstract public function getErrorHandlers(): array;
}

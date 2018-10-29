<?php

namespace App;

abstract class Kernel extends \Riki\Kernel
{
    abstract public function getErrorHandlers(Application $app): array;
}

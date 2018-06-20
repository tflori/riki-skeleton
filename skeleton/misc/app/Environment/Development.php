<?php

namespace App\Environment;

use App\Environment;

class Development extends Environment
{
    public function canCacheConfig(): bool
    {
        return false;
    }

    public function canShowErrors()
    {
        return true;
    }
}

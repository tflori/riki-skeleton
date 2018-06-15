<?php

namespace App\Environment;

class Development extends Base
{
    public function canCacheConfig(): bool
    {
        return false;
    }
}

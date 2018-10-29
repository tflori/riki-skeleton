<?php

namespace App\Environment;

use App\Environment;

/**
 * Class Development
 *
 * @package App\Environment
 * @codeCoverageIgnore Environment will not be loaded in tests
 */
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

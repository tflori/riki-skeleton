<?php

namespace App\Environment;

use Riki\Environment;

class Base extends Environment
{
    public function storagePath(string $path = null): string
    {
        $storagePath = $this->getBasePath() . '/storage';
        if ($path) {
            $storagePath .= '/' . ltrim($path, '/');
        }
        return $storagePath;
    }

    public function getConfigCachePath(): string
    {
        $path = $this->storagePath('cache/config.spo');
        return $path; // serialized php object
    }
}

<?php

namespace App;

class Environment extends \Riki\Environment
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

    public function logPath(string $path = null): string
    {
        $logPath = $this->storagePath('logs');
        if ($path) {
            $logPath .= '/' . ltrim($path, '/');
        }
        return $logPath;
    }

    public function canShowErrors()
    {
        return false;
    }
}

<?php

namespace App;

/**
 * Class Environment
 *
 * @package App
 * @codeCoverageIgnore trivial code
 */
class Environment extends \Riki\Environment
{
    public function storagePath(string $path = null): string
    {
        return $this->getBasePath() . '/storage' . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function cachePath(string $path = null): string
    {
        return $this->storagePath('cache' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function getConfigCachePath(): string
    {
        return $this->cachePath('config.spo');
    }

    public function logPath(string $path = null): string
    {
        return $this->storagePath('logs' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function canShowErrors()
    {
        return false;
    }
}

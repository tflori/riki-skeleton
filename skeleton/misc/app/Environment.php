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
    public function canShowErrors()
    {
        return false;
    }

    public function getConfigCachePath(): string
    {
        return $this->cachePath('config.spo');
    }

    public function storagePath(string ...$path): string
    {
        return $this->path('storage', ...$path);
    }

    public function cachePath(string ...$path): string
    {
        return $this->storagePath('cache', ...$path);
    }

    public function logPath(string ...$path): string
    {
        return $this->storagePath('logs', ...$path);
    }

    protected function path(string ...$path)
    {
        array_unshift($path, $this->getBasePath());
        return implode(DIRECTORY_SEPARATOR, $path);
    }
}

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
    protected function path(string ...$path)
    {
        array_unshift($path, $this->getBasePath());
        return implode(DIRECTORY_SEPARATOR, $path);
    }

    public function storagePath(string ...$path): string
    {
        return $this->path('storage', ...$path);
    }

    public function cachePath(string ...$path): string
    {
        return $this->storagePath('cache', ...$path);
    }

    public function getConfigCachePath(): string
    {
        return $this->cachePath('config.spo');
    }

    public function logPath(string ...$path): string
    {
        return $this->storagePath('logs', ...$path);
    }

    public function resourcePath(string ...$path)
    {
        return $this->path('resources', ...$path);
    }

    public function viewPath(string ...$path): string
    {
        return $this->resourcePath('views', ...$path);
    }

    public function canShowErrors()
    {
        return false;
    }
}

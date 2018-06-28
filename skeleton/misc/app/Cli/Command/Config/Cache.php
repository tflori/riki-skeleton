<?php

namespace App\Cli\Command\Config;

use App\Cli\AbstractCommand;
use App\Config;
use GetOpt\GetOpt;

class Cache extends AbstractCommand
{
    protected $name = 'config:cache';

    protected $description = 'Create a configuration cache when caching is enabled for this environment.';

    public function handle(GetOpt $getOpt): int
    {
        if (!$this->app->environment->canCacheConfig()) {
            $this->console->warn('The environment does not allow to cache the configuration!');
            return 0;
        }

        $cachePath = $this->app->environment->getConfigCachePath();
        if (!file_exists(dirname($cachePath)) && !mkdir(dirname($cachePath), umask() ^ 0777, true)) {
            $this->console->error('Could not create parent directory for caching!');
            return 1;
        } elseif (!is_writeable(dirname($cachePath)) || !is_dir(dirname($cachePath))) {
            $this->console->error('Cache directory is not writeable!');
            return 2;
        }

        // create a fresh configuration (don't use the cached version)
        $config = new Config($this->app->environment);
        if (!file_put_contents($cachePath, serialize($config))) {
            $this->console->error('Failed to cache the configuration!');
            return 3;
        }

        $this->console->info('Configuration cache created successfully!');
        return 0;
    }
}

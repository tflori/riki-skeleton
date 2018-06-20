<?php

namespace App;

use DependencyInjector\Container;
use Riki\Exception;

class Application extends Container
{
    /** @var callable[] */
    protected $bootstrappers = [];

    /** @var string */
    protected $basePath;

    /** @var string  */
    protected $fallbackEnvironment = 'App\Environment';

    /** @var string  */
    protected $environmentNamespace = 'App\Environment';

    /** @var string */
    protected $configClass = 'App\Config';

    public function __construct(string $basePath)
    {
        parent::__construct();
        $this->alias('container', Application::class);
        $this->alias('container', 'app');
        \DependencyInjector\DI::setContainer($this);

        $this->basePath = $basePath;

        $this->addBootstrappers(
            [$this, 'detectEnvironment'],
            [$this, 'loadConfig']
        );
    }

    public function run(Kernel $kernel, ...$args)
    {
        $this->bootstrap(...$kernel->getBootstrappers());
        return $kernel->handle(...$args);
    }

    protected function bootstrap(callable ...$kernelBootstrappers)
    {
        $bootstrappers = $this->getBootstrappers();
        array_push($bootstrappers, ...$kernelBootstrappers);
        foreach ($bootstrappers as $bootstrapper) {
            try {
                if (!$bootstrapper($this)) {
                    throw new \Exception('Unknown error');
                }
            } catch (\Throwable | \Exception $ex) {
                throw new Exception('Unexpected exception in bootstrap process', 0, $ex);
            }
        }
    }

    public function detectEnvironment(Application $app): bool
    {
        if ($app->has('environment')) {
            return true;
        }

        $classes = [ $this->fallbackEnvironment ];
        $appEnv = getenv('APP_ENV') ?: 'development';
        $classes[] = $this->environmentNamespace . '\\' . ucfirst($appEnv);
        if (PHP_SAPI === 'cli') {
            $classes[] = $this->environmentNamespace . '\\' . ucfirst($appEnv) . 'Cli';
        }
        foreach (array_reverse($classes) as $class) {
            if (class_exists($class)) {
                $this->instance('environment', new $class);
                $this->alias('environment', $this->fallbackEnvironment);
                return true;
            }
        }

        throw new Exception('No environment found');
    }

    public function loadConfig(Application $app): bool
    {
        if ($app->has('config')) {
            return true;
        }

        /** @var \Riki\Environment $environment */
        $environment = $this->get('environment');
        if ($environment->canCacheConfig() && file_exists($environment->getConfigCachePath())) {
            $config = unserialize(file_get_contents($environment->getConfigCachePath()));
        } else {
            $class = $this->configClass;
            $config =  new $class($environment);
        }

        if (!$config) {
            throw new Exception('Configuration not found');
        }

        $this->instance('config', $config);
        $this->alias('config', $this->configClass);
        return true;
    }

    public function addBootstrappers(callable ...$bootstrapper)
    {
        array_push($this->bootstrappers, ...$bootstrapper);
        return $this;
    }

    public function getBootstrappers()
    {
        return $this->bootstrappers;
    }
}

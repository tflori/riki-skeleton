<?php

namespace App;

use Http\Response;
use Monolog\Logger;
use Whoops;
use Whoops\Handler\PlainTextHandler;

/**
 * Class Application
 *
 * @package App
 *
 * @method static Application app()
 * @method static Environment environment()
 * @method static Config config()
 * @method static Logger logger()
 * @property-read Application $app
 * @property-read Environment $environment
 * @property-read Config $config
 * @property-read Logger $logger
 */
class Application extends \Riki\Application
{
    /** @var Whoops\Run */
    protected $whoops;

    /** @var array */
    protected $errorHandlers;

    public function __construct(string $basePath)
    {
        parent::__construct($basePath);

        // bootstrap the application
        $this->initWhoops();
    }

    protected function initDependencies()
    {
        parent::initDependencies();

        // Register a namespace for factories
        $this->registerNamespace('App\Factory', 'Factory');

        // Register Whoops\Run under whoops
        $this->share('whoops', Whoops\Run::class);
    }


    /**
     * @return bool
     */
    public function initWhoops()
    {
        /** @var Whoops\Run $whoops */
        $whoops = $this->get('whoops');
        $whoops->register();
        $this->setErrorHandlers(...$this->getErrorHandlers());
        return true;
    }

    public function run(\Riki\Kernel $kernel, ...$args)
    {
        if ($kernel instanceof Kernel) {
            $this->setErrorHandlers(...$kernel->getErrorHandlers($this), ...$this->getErrorHandlers());
        }

        $result = parent::run($kernel, ...$args);

        if ($kernel instanceof Kernel) {
            // @todo this should be shift and unshift of kernels error handlers but there is only push and pop
            $this->setErrorHandlers(...$this->getErrorHandlers());
        }

        return $result;
    }

    protected function getErrorHandlers()
    {
        if (!$this->errorHandlers) {
            $plainTextHandler = new PlainTextHandler($this->logger);
            $plainTextHandler->loggerOnly(true);
            $this->errorHandlers = [$plainTextHandler];
        }

        return $this->errorHandlers;
    }

    protected function setErrorHandlers(...$handlers)
    {
        /** @var Whoops\Run $whoops */
        $whoops = $this->get('whoops');
        $whoops->clearHandlers();
        foreach ($handlers as $handler) {
            $whoops->pushHandler($handler);
        }
    }
}

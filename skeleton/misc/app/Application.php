<?php

namespace App;

use Http\Response;
use Monolog\Logger;
use Whoops;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

/**
 * Class Application
 *
 * @package App
 *
 * @property-read Environment $environment
 * @property-read Config $config
 * @property-read Logger $logger
 * @property-read Whoops\Run $whoops
 * @property-read Response $response
 */
class Application extends \Riki\Application
{
    public function __construct(string $basePath)
    {
        parent::__construct($basePath);
        $this->addBootstrappers([$this, 'initWhoops']);
        $this->registerNamespace('App\Factory');
    }

    public function initWhoops()
    {
        $whoops = new Whoops\Run();
        $handler = new PlainTextHandler();
        $handler->setLogger($this->logger);
        $handler->loggerOnly(true);
        $whoops->pushHandler($handler);
        $whoops->register();
        $this->instance('whoops', $whoops);

        return true;
    }
}

<?php

namespace App;

use Http\Response;
use Monolog\Logger;
use Whoops;
use Whoops\Handler\HandlerInterface;
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
 * @property-read Response $response
 */
class Application extends \Riki\Application
{
    /** @var Whoops\Run */
    protected $whoops;

    public function __construct(string $basePath)
    {
        parent::__construct($basePath);
        $this->addBootstrappers([$this, 'initWhoops']);
        $this->registerNamespace('App\Factory');
    }

    /**
     * @return bool
     * @codeCoverageIgnore We can not register whoops in tests
     */
    public function initWhoops()
    {
        $whoops = new Whoops\Run();
        $whoops->register();
        $this->whoops = $whoops;

        $handler = new PlainTextHandler();
        $handler->setLogger($this->logger);
        $handler->loggerOnly(true);
        $this->appendWhoopsHandler($handler);

        return true;
    }

    /**
     * @param callable|HandlerInterface $handler
     */
    public function appendWhoopsHandler($handler)
    {
        $handlers = $this->whoops->getHandlers();
        array_unshift($handlers, $handler);
        $this->whoops->clearHandlers();
        foreach ($handlers as $handler) {
            $this->whoops->pushHandler($handler);
        }
    }
}

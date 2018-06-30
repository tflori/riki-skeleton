<?php

namespace App;

use Http\Response;
use Hugga\Console;
use Monolog\Logger;
use Whoops;
use Whoops\Handler\HandlerInterface;
use Whoops\Handler\PlainTextHandler;

/**
 * Class Application
 *
 * @package App
 *
 * @property-read Environment $environment
 * @property-read Config $config
 * @property-read Logger $logger
 * @property-read Response $response
 * @property-read Console $console
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
        $whoops = $this->whoops = new Whoops\Run();
        $whoops->register();

        // default error logging
        $handler = new PlainTextHandler();
        $handler->setLogger($this->logger);
        $handler->loggerOnly(true);
        $whoops->pushHandler($handler);

        return true;
    }

    /**
     * Prepends $handler to the list of handlers from whoops.
     *
     * Because whoops executes the handlers in reverse order they will run as last handler - so they are appended.
     *
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

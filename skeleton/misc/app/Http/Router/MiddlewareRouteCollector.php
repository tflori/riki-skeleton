<?php

namespace App\Http\Router;

class MiddlewareRouteCollector extends \FastRoute\RouteCollector
{
    /** @var array */
    protected $handler = [];

    public function addHandler(...$handler)
    {
        array_push($this->handler, ...$handler);
    }

    public function setHandler(...$handler)
    {
        $this->handler = $handler;
    }

    public function addGroup($prefix, callable $callback)
    {
        $previousHandler = $this->handler;
        parent::addGroup($prefix, $callback);
        if ($this->dataGenerator instanceof MiddlewareDataGenerator) {
            $routeData = $this->routeParser->parse($prefix);
            $this->dataGenerator->addGroup($routeData[0], $this->handler);
        }
        $this->handler = $previousHandler;
    }

    public function addRoute($httpMethod, $route, $handler, ...$handlers)
    {
        array_unshift($handlers, $handler);
        parent::addRoute($httpMethod, $route, array_merge($this->handler, $handlers));
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function get($route, $handler, ...$handlers)
    {
        $this->addRoute('GET', $route, $handler, ...$handlers);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function post($route, $handler, ...$handlers)
    {
        $this->addRoute('POST', $route, $handler, ...$handlers);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function put($route, $handler, ...$handlers)
    {
        $this->addRoute('PUT', $route, $handler, ...$handlers);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function delete($route, $handler, ...$handlers)
    {
        $this->addRoute('DELETE', $route, $handler, ...$handlers);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function patch($route, $handler, ...$handlers)
    {
        $this->addRoute('PATH', $route, $handler, ...$handlers);
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore just an alias
     */
    public function head($route, $handler, ...$handlers)
    {
        $this->addRoute('HEAD', $route, $handler, ...$handlers);
    }
}

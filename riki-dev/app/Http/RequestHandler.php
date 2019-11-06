<?php

namespace App\Http;

use App\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandler implements RequestHandlerInterface
{
    /** @var Application */
    protected $app;

    /** @var string */
    protected $controller;

    /** @var string */
    protected $method;

    /**
     * RequestHandler constructor.
     *
     * @param Application $app
     * @param string $controller
     * @param string $method
     */
    public function __construct(Application $app, string $controller, string $method)
    {
        $this->app = $app;
        $this->controller = $controller;
        $this->method = $method;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = $this->app->make($this->controller, $this->app);

        if (!method_exists($controller, $this->method)) {
            throw new \Exception(sprintf('Action %s is unknown in %s', $this->method, $this->controller));
        }

        $arguments = $request->getAttribute('arguments') ?? [];
        return call_user_func([$controller, $this->method], $request, ...array_values($arguments));
    }
}

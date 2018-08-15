<?php

namespace App\Http\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class AbstractController implements RequestHandlerInterface
{
    /** @var ServerRequestInterface */
    protected $request;

    /** @var string */
    protected $action;

    public function __construct($action)
    {
        $this->action = $action;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $action = $this->action;

        if (!method_exists($this, $action)) {
            throw new \Exception(sprintf('Action %s is unknown in %s', $action, static::class));
        }

        $arguments = $request->getAttribute('arguments') ?? [];
        $response = call_user_func([$this, $action], ...$arguments);

        return $response;
    }
}

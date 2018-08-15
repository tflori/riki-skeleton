<?php

namespace App\Http;

use App\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Queue Dispatcher
 *
 * The queue dispatcher is a simple one class middleware dispatcher.
 *
 * A handler in queue can either be a string in form of 'Controller@method', a class name of a middleware or request
 * handler or a callable that may act as request handler or middleware.
 *
 * A callable will get two parameters: a ServerRequestInterface and a callable for next. An example could be:
 * ```php
 * function (ServerRequestInterface $request, callable($next)): ResponseInterface {
 *     // optional modify request
 *     $request = $request->withAttribute('foo', 'bar');
 *
 *     // optional return early
 *     return new ServerResponse(403);
 *
 *     // optional call next (optional without $request)
 *     $response = $next($request);
 *
 *     // mandatory return a ResponseInterface;
 *     return $response;
 * }
 * ```
 *
 * @package App\Http
 * @author Thomas Flori <thflori@gmail.com>
 */
class Dispatcher implements RequestHandlerInterface
{
    /** @var array */
    protected $queue;

    /** @var callable */
    protected $resolver;

    /**
     * Dispatcher constructor.
     *
     * @param array $queue
     * @param callable $resolver
     */
    public function __construct(array $queue, callable $resolver)
    {
        $this->queue = $queue;
        $this->resolver = $resolver;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = call_user_func($this->resolver, array_shift($this->queue));

        if ($handler instanceof MiddlewareInterface) {
            return $handler->process($request, $this);
        }

        if ($handler instanceof RequestHandlerInterface) {
            return $handler->handle($request);
        }

        return $handler($request, $this);
    }
}

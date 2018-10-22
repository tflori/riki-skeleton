<?php

namespace Test\Unit\Http\Router;

use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use App\Http\Router\MiddlewareRouter;
use FastRoute\Dispatcher;
use FastRoute\RouteParser;
use Test\TestCase;

/**
 * @covers \App\Http\Router\MiddlewareRouter
 */
class MiddlewareRouterTest extends TestCase
{
    /** @test */
    public function addsGroupHandlersWhenNotFound()
    {
        // prepare routes we have to
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());
        $routeCollector->addGroup('/foo', function (MiddlewareRouteCollector $routeCollector) {
            $routeCollector->addHandler('fooHandler');
        });
        $router = new MiddlewareRouter($routeCollector->getData());

        $result = $router->dispatch('GET', '/foo/bar');

        self::assertSame(
            [Dispatcher::NOT_FOUND, ['fooHandler']],
            $result
        );
    }

    /** @test */
    public function addsGroupHandlersWhenMethodNotAllowed()
    {
        // prepare routes we have to
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());
        $routeCollector->addGroup('/foo', function (MiddlewareRouteCollector $routeCollector) {
            $routeCollector->addHandler('fooHandler');
            $routeCollector->get('/bar', 'barHandler');
        });
        $router = new MiddlewareRouter($routeCollector->getData());

        $result = $router->dispatch('POST', '/foo/bar');

        self::assertSame(
            [Dispatcher::METHOD_NOT_ALLOWED, ['GET'], ['fooHandler']],
            $result
        );
    }
}

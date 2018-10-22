<?php

namespace Test\Unit\Http\Router;

use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use FastRoute\RouteParser;
use Test\TestCase;

/**
 * @covers App\Http\Router\MiddlewareRouteCollector
 */
class MiddlewareRouteCollectorTest extends TestCase
{
    /** @test */
    public function createsArraysOfHandlers()
    {
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());

        $routeCollector->addRoute('GET', '/foo', 'fooHandler');

        self::assertSame(
            ['fooHandler'],
            $routeCollector->getData()[0]['GET']['/foo']
        );
    }

    /** @test */
    public function prependsAddedHandlers()
    {
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());

        $routeCollector->addHandler('middleware1', 'middleware2');
        $routeCollector->addRoute('GET', '/foo', 'fooHandler');

        self::assertSame(
            ['middleware1', 'middleware2', 'fooHandler'],
            $routeCollector->getData()[0]['GET']['/foo']
        );
    }

    /** @test */
    public function routesCanHaveSeveralHandlers()
    {
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());

        $routeCollector->addHandler('middleware1', 'middleware2');
        $routeCollector->addRoute('GET', '/foo/bar', 'fooHandler', 'barHandler');

        self::assertSame(
            ['middleware1', 'middleware2', 'fooHandler', 'barHandler'],
            $routeCollector->getData()[0]['GET']['/foo/bar']
        );
    }

    /** @test */
    public function prependedHeadersAreOnlyAddedInsideGroups()
    {
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());
        $routeCollector->addHandler('middleware1');

        $routeCollector->addGroup('/foo', function (MiddlewareRouteCollector $routeCollector) {
            $routeCollector->addHandler('middleware2');
            $routeCollector->addRoute('GET', '/bar', 'barHandler');
        });
        $routeCollector->get('/fooBar', 'fooHandler', 'barHandler');

        self::assertSame(
            ['middleware1', 'middleware2', 'barHandler'],
            $routeCollector->getData()[0]['GET']['/foo/bar']
        );
        self::assertSame(
            ['middleware1', 'fooHandler', 'barHandler'],
            $routeCollector->getData()[0]['GET']['/fooBar']
        );
    }

    /** @test */
    public function addsAGroupMatcher()
    {
        $routeCollector = new MiddlewareRouteCollector(new RouteParser\Std(), new MiddlewareDataGenerator());
        $routeCollector->addHandler('middleware1');

        $routeCollector->addGroup('/foo', function (MiddlewareRouteCollector $routeCollector) {
            $routeCollector->addHandler('middleware2');
        });

        self::assertSame(
            [
                'regex' => '~^(?|/foo)~',
                'groupMap' => [
                    1 => ['middleware1', 'middleware2']
                ]
            ],
            $routeCollector->getData()[2]
        );
    }
}

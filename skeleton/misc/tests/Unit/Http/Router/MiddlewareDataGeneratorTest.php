<?php

namespace Test\Unit\Http\Router;

use App\Http\Router\MiddlewareDataGenerator;
use FastRoute\BadRouteException;
use FastRoute\RouteParser;
use Test\TestCase;

/**
 * @covers App\Http\Router\MiddlewareDataGenerator
 */
class MiddlewareDataGeneratorTest extends TestCase
{
    /** @test */
    public function acceptsOnlyStaticPrefixes()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();

        self::expectException(BadRouteException::class);
        self::expectExceptionMessage('Groups can only have static routes');

        $dataGenerator->addGroup($routeParser->parse('/{any}')[0], 'handler');
    }

    /** @test */
    public function addsGroupsToRouteData()
    {
        $dataGenerator = new MiddlewareDataGenerator();

        self::assertCount(3, $dataGenerator->getData());
        self::assertSame([], $dataGenerator->getData()[2]);
    }

    /** @test */
    public function acceptsStaticPrefixes()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();
        $routeData = $routeParser->parse('/foo/bar')[0];

        $dataGenerator->addGroup($routeData, 'handler');

        self::assertSame(
            [1 => 'handler'],
            $dataGenerator->getData()[2]['groupMap']
        );
    }

    /** @test */
    public function doesNotAllowDuplicatedPrefixes()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();
        $routeData = $routeParser->parse('/foo/bar')[0];
        $dataGenerator->addGroup($routeData, 'handler');

        self::expectException(BadRouteException::class);
        self::expectExceptionMessage('Cannot register two groups matching "');

        $dataGenerator->addGroup($routeData, 'anotherHandler');
    }

    /** @test */
    public function eachGroupAddsAnotherSubPattern()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();

        $dataGenerator->addGroup($routeParser->parse('/foo')[0], 'fooHandler');
        $dataGenerator->addGroup($routeParser->parse('/bar')[0], 'barHandler');
        $dataGenerator->addGroup($routeParser->parse('/baz')[0], 'bazHandler');

        self::assertSame(
            '~^(?|/foo|/bar()|/baz()())~',
            $dataGenerator->getData()[2]['regex']
        );
    }

    /** @test */
    public function routesAreSortedByStrLen()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();

        $dataGenerator->addGroup($routeParser->parse('/long/route')[0], 'fooHandler');
        $dataGenerator->addGroup($routeParser->parse('/short')[0], 'barHandler');
        $dataGenerator->addGroup($routeParser->parse('/longer/route')[0], 'fooHandler');

        self::assertSame(
            '~^(?|/longer/route|/long/route()|/short()())~',
            $dataGenerator->getData()[2]['regex']
        );
    }

    /** @test */
    public function returnsMapWithExpectedGroupsForHandlers()
    {
        $dataGenerator = new MiddlewareDataGenerator();
        $routeParser = new RouteParser\Std();

        $dataGenerator->addGroup($routeParser->parse('/foo')[0], 'fooHandler');
        $dataGenerator->addGroup($routeParser->parse('/bar')[0], 'barHandler');
        $dataGenerator->addGroup($routeParser->parse('/baz')[0], 'bazHandler');

        self::assertSame(
            [
                1 => 'fooHandler',
                2 => 'barHandler',
                3 => 'bazHandler',
            ],
            $dataGenerator->getData()[2]['groupMap']
        );
    }
}

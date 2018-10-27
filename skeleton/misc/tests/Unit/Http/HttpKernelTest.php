<?php

namespace Test\Unit\Http;

use App\Http\Controller\ErrorController;
use App\Http\Dispatcher;
use App\Http\HttpKernel;
use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use App\Http\Router\MiddlewareRouter;
use FastRoute\Dispatcher as RouteDispatcher;
use FastRoute\RouteParser\Std;
use Tal\Psr7Extended\ServerRequestInterface;
use Tal\ServerRequest;
use Tal\ServerResponse;
use Test\TestCase;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Mockery as m;

class HttpKernelTest extends TestCase
{
    /** @test */
    public function definesACustomErrorHandler()
    {
        $kernel = new HttpKernel();
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(false);

        $result = $kernel->getErrorHandlers($this->app);

        self::assertInstanceOf(\Closure::class, $result[0]);
    }

    /** @test */
    public function definesAPrettyPageHandler()
    {
        $kernel = new HttpKernel();
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(true);

        $result = $kernel->getErrorHandlers($this->app);

        self::assertInstanceOf(PrettyPageHandler::class, $result[0]);
    }

    /** @test */
    public function customErrorHandlerRendersAnUnexpectedError()
    {
        $exception = new \Exception('Any message');
        $errorController = m::mock(ErrorController::class);
        $response = m::mock(ServerResponse::class);
        $this->app->instance(ErrorController::class, $errorController);
        $errorController->shouldReceive('unexpectedError')->with($exception)
            ->once()->andReturn($response);
        $response->shouldReceive('send')->with()
            ->once();

        $kernel = new HttpKernel();
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(false);
        $handler = $kernel->getErrorHandlers($this->app)[0];

        $result = $handler($exception);

        self::assertSame(Handler::QUIT, $result);
    }

    /**  @dataProvider provideCallables
     * @param callable $callable
     * @test */
    public function returnsTheCallable(callable $callable)
    {
        $result = HttpKernel::getHandler($callable);

        self::assertSame($callable, $result);
    }

    public function provideCallables()
    {
        return [
            ['strlen'],
            [[HttpKernel::class, 'getHandler']],
            [function () {
            }],
            [HttpKernel::class . '::' . 'getHandler'],
            [[new ErrorController('unexpectedError'), 'handle']],
        ];
    }

    /** @test */
    public function checksIfCallableIsCallable()
    {
        if (!is_callable([ErrorController::class, 'unexpectedError'])) {
            $this->markTestSkipped('Only relevant when static calls to non static methods are callable');
            return;
        }

        $errorController = new ErrorController('unexpectedError');
        $this->app->shouldReceive('make')->with(ErrorController::class, 'unexpectedError')
            ->once()->andReturn($errorController);

        $result = HttpKernel::getHandler([ErrorController::class, 'unexpectedError']);

        self::assertSame($errorController, $result);
    }

    /** @test */
    public function throwsWhenClassDoesNotExist()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Class FooClass not found');

        HttpKernel::getHandler('FooClass');
    }

    /** @test */
    public function createsClassWithArguments()
    {
        $routeParser = new Std();
        $dataGenerator = new MiddlewareDataGenerator();
        $routeCollector = new MiddlewareRouteCollector($routeParser, $dataGenerator);
        $this->app->shouldReceive('make')->with(MiddlewareRouteCollector::class, $routeParser, $dataGenerator)
            ->once()->andReturn($routeCollector);

        $result = HttpKernel::getHandler([MiddlewareRouteCollector::class, $routeParser, $dataGenerator]);

        self::assertSame($routeCollector, $result);
    }

    /** @test */
    public function acceptsTheControllerForm()
    {
        $errorController = new ErrorController('unexpectedError');
        $this->app->shouldReceive('make')->with(ErrorController::class, 'unexpectedError')
            ->once()->andReturn($errorController);

        HttpKernel::getHandler('unexpectedError@' . ErrorController::class);
    }

    /** @test */
    public function findsTheControllerInNamespace()
    {
        $errorController = new ErrorController('unexpectedError');
        $this->app->shouldReceive('make')->with(ErrorController::class, 'unexpectedError')
            ->once()->andReturn($errorController);

        HttpKernel::getHandler('unexpectedError@ErrorController');
    }

    /** @test */
    public function dispatchesFoundRoute()
    {
        $method = 'GET';
        $uri = '/23';
        $routerResponse = [RouteDispatcher::FOUND, ['fooHandler', 'barHandler'], ['id' => 23]];
        $expectedHandlers = ['fooHandler', 'barHandler'];
        $expectedArguments = ['id' => 23];

        $this->mockDispatcher($method, $uri, $routerResponse, $expectedHandlers, $expectedArguments);
    }

    /** @test */
    public function dispatchesMethodNotAllowedError()
    {
        $method = 'POST';
        $uri = '/23';
        $routerResponse = [RouteDispatcher::METHOD_NOT_ALLOWED, ['GET'], ['middleware']];
        $expectedHandlers = ['middleware', [ErrorController::class, 'methodNotAllowed']];
        $expectedArguments = ['allowedMethods' => ['GET']];

        $this->mockDispatcher($method, $uri, $routerResponse, $expectedHandlers, $expectedArguments);
    }

    /** @test */
    public function dispatchesNotFoundError()
    {
        $method = 'GET';
        $uri = '/any/route';
        $routerResponse = [RouteDispatcher::NOT_FOUND, ['middleware']];
        $expectedHandlers = ['middleware', [ErrorController::class, 'notFound']];

        $this->mockDispatcher($method, $uri, $routerResponse, $expectedHandlers);
    }

    /** @test */
    public function dispatchesUnexpectedError()
    {
        $method = 'GET';
        $uri = '/anything';
        $routerResponse = [RouteDispatcher::FOUND, ['anyHandler'], []
        ];

        $request = new ServerRequest($method, $uri);
        $exception = new \Exception('This was expected');

        /** @var MiddlewareRouter|m\Mock $kernel */
        $router = $this->mocks['router'] = m::mock(MiddlewareRouter::class);
        $this->app->instance(MiddlewareRouter::class, $router);

        /** @var HttpKernel|m\Mock $kernel */
        $kernel = $this->mocks['kernel'] = m::mock(HttpKernel::class)->makePartial();
        $kernel->loadRoutes($this->app);

        /** @var Dispatcher|m\Mock $dispatcher */
        $dispatcher = $this->mocks['dispatcher'] = m::mock(Dispatcher::class);

        // first: dispatches the method and uri to router
        $router->shouldReceive('dispatch')->with($method, $uri)
            ->once()->andReturn($routerResponse)->ordered();

        // second: creates the dispatcher
        $this->app->shouldReceive('make')
            ->with(Dispatcher::class, ['anyHandler'], [HttpKernel::class, 'getHandler'])
            ->once()->andReturn($dispatcher)->ordered();

        // third: dispatches the request to dispatcher
        $dispatcher->shouldReceive('handle')->with(m::type(ServerRequest::class))
            ->once()->andThrow($exception)->ordered();

        // fifth: error controller gets generated
        $this->app->shouldReceive('make')
            ->with(ErrorController::class, 'unexpectedError')
            ->once()->andReturn($errorController = m::mock(ErrorController::class))->ordered();

        // six: error controller gets called
        $errorController->shouldReceive('handle')->with(m::type(ServerRequest::class))
            ->once()->andReturnUsing(function (ServerRequestInterface $dispatched) use (&$request) {
                // we store the request that got dispatched
                $request = $dispatched;
                return new ServerResponse();
            })->ordered();

        $kernel->handle($request);

        self::assertSame(['exception' => $exception], $request->getAttribute('arguments'));
    }

    protected function mockDispatcher(
        string $method,
        string $uri,
        array $routerResponse,
        array $expectedHandlers,
        array $expectedArguments = null
    ) {
        $request = new ServerRequest($method, $uri);

        /** @var MiddlewareRouter|m\Mock $kernel */
        $router = $this->mocks['router'] = m::mock(MiddlewareRouter::class);
        $this->app->instance(MiddlewareRouter::class, $router);

        /** @var HttpKernel|m\Mock $kernel */
        $kernel = $this->mocks['kernel'] = m::mock(HttpKernel::class)->makePartial();
        $kernel->loadRoutes($this->app);

        /** @var Dispatcher|m\Mock $dispatcher */
        $dispatcher = $this->mocks['dispatcher'] = m::mock(Dispatcher::class);

        // first: dispatches the method and uri to router
        $router->shouldReceive('dispatch')->with($method, $uri)
            ->once()->andReturn($routerResponse)->ordered();

        // second: creates the dispatcher
        $this->app->shouldReceive('make')
            ->with(Dispatcher::class, $expectedHandlers, [HttpKernel::class, 'getHandler'])
            ->once()->andReturn($dispatcher)->ordered();

        // third: dispatches the request to dispatcher
        $dispatcher->shouldReceive('handle')->with(m::type(ServerRequest::class))
            ->once()->andReturnUsing(function (ServerRequestInterface $dispatched) use (&$request) {
                // we store the request that got dispatched
                $request = $dispatched;
                return new ServerResponse();
            })->ordered();

        $kernel->handle($request);

        self::assertSame($expectedArguments, $request->getAttribute('arguments'));
        return $request;
    }
}

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
    public function usesHandlersAndArgumentsFromRouter()
    {
        $this->app->instance(MiddlewareRouter::class, $router = m::mock(MiddlewareRouter::class));
        /** @var HttpKernel|m\Mock $kernel */
        $kernel = m::mock(HttpKernel::class)->makePartial();
        $kernel->loadRoutes($this->app);
        $request = new ServerRequest('GET', '/23');
        $response = new ServerResponse();

        $router->shouldReceive('dispatch')->with('GET', '/23')
            ->once()->andReturn([RouteDispatcher::FOUND, ['fooHandler', 'barHandler'], ['id' => 23]]);
        $this->app->shouldReceive('make')
            ->with(Dispatcher::class, ['fooHandler', 'barHandler'], [HttpKernel::class, 'getHandler'])
            ->once()->andReturn($dispatcher = m::mock(Dispatcher::class));
        $dispatcher->shouldReceive('handle')->with(m::type(ServerRequest::class))
            ->once()->andReturnUsing(function (ServerRequestInterface $request) use ($response) {
                self::assertSame(['id' => 23], $request->getAttribute('arguments'));
                return $response;
            });

        $result = $kernel->handle($request);

        self::assertSame($response, $result);
    }
}

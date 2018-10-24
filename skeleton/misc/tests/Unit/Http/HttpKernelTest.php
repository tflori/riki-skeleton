<?php

namespace Test\Unit\Http;

use App\Http\Controller\ErrorController;
use App\Http\HttpKernel;
use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use FastRoute\RouteParser\Std;
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
}

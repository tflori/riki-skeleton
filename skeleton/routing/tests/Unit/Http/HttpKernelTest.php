<?php

namespace Test\Unit\Http;

use App\Application;
use App\Http\Controller\ErrorController;
use App\Http\RequestHandler;
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
use Test\Example\CustomController;
use Test\TestCase;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Mockery as m;

class HttpKernelTest extends TestCase
{
    /** @test */
    public function definesACustomErrorHandler()
    {
        $kernel = new HttpKernel($this->app);
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(false);

        $result = $kernel->getErrorHandlers();

        self::assertInstanceOf(\Closure::class, $result[0]);
    }

    /** @test */
    public function definesAPrettyPageHandler()
    {
        $kernel = new HttpKernel($this->app);
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(true);

        $result = $kernel->getErrorHandlers();

        self::assertInstanceOf(PrettyPageHandler::class, $result[0]);
    }

    /** @test */
    public function customErrorHandlerRendersAnUnexpectedError()
    {
        $exception = new \Exception('Any message');
        $errorController = m::mock(ErrorController::class);
        $response = m::mock(ServerResponse::class);
        $this->app->instance(ErrorController::class, $errorController);
        $errorController->shouldReceive('unexpectedError')->with(m::type(ServerRequest::class), $exception)
            ->once()->andReturn($response);
        $response->shouldReceive('send')->with()
            ->once();

        $kernel = new HttpKernel($this->app);
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
        $httpKernel = new HttpKernel($this->app);

        $result = $httpKernel->getHandler($callable);

        self::assertSame($callable, $result);
    }

    public function provideCallables()
    {
        return [
            ['strlen'],
            [[CustomController::class, 'doSomething']],
            [function () {
            }],
            [CustomController::class . '::' . 'doSomething'],
            [[new ErrorController(m::mock(Application::class)), 'unexpectedError']],
        ];
    }

    /** @test */
    public function checksIfCallableIsCallable()
    {
        if (!is_callable([ErrorController::class, 'unexpectedError'])) {
            $this->markTestSkipped('Only relevant when static calls to non static methods are callable');
            return;
        }

        $httpKernel = new HttpKernel($this->app);

        $handler = $httpKernel->getHandler([ErrorController::class, 'unexpectedError']);

        self::assertInstanceOf(RequestHandler::class, $handler);
    }

    /** @test */
    public function returnsAnInstanceOfAClass()
    {
        $httpKernel = new HttpKernel($this->app);

        $dispatcher = new RequestHandler($this->app, ErrorController::class, 'unexpectedError');
        $this->app->shouldReceive('make')->with(RequestHandler::class)
            ->once()->andReturn($dispatcher);

        $handler = $httpKernel->getHandler(RequestHandler::class);

        self::assertSame($dispatcher, $handler);
    }

    /** @test */
    public function throwsWhenClassDoesNotExist()
    {
        $httpKernel = new HttpKernel($this->app);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Class FooClass not found');

        $httpKernel->getHandler('FooClass');
    }

    /** @test */


    public function throwsWhenNoCallableGiven()
    {
        $httpKernel = new HttpKernel($this->app);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(
            '$handler has to be a callable, a string in form "method@Controller" or a class name'
        );

        $httpKernel->getHandler(['class' => ErrorController::class, 'method' => 'unexpectedError']);
    }

    /** @test */
    public function acceptsTheControllerForm()
    {
        $httpKernel = new HttpKernel($this->app);

        $handler = $httpKernel->getHandler('unexpectedError@' . ErrorController::class);

        self::assertEquals(
            new RequestHandler($this->app, ErrorController::class, 'unexpectedError'),
            $handler
        );
    }

    /** @test */
    public function findsTheControllerInNamespace()
    {
        $httpKernel = new HttpKernel($this->app);

        $handler = $httpKernel->getHandler('unexpectedError@ErrorController');

        self::assertEquals(
            new RequestHandler($this->app, ErrorController::class, 'unexpectedError'),
            $handler
        );
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
        $kernel->__construct($this->app);

        /** @var Dispatcher|m\Mock $dispatcher */
        $dispatcher = $this->mocks['dispatcher'] = m::mock(Dispatcher::class);

        // first: dispatches the method and uri to router
        $router->shouldReceive('dispatch')->with($method, $uri)
            ->once()->andReturn($routerResponse)->ordered();

        // second: creates the dispatcher
        $this->app->shouldReceive('make')
            ->with(Dispatcher::class, $expectedHandlers, [$kernel, 'getHandler'])
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

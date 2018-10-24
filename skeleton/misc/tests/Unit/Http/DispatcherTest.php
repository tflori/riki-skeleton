<?php

namespace Test\Unit\Http;

use App\Http\Controller\ErrorController;
use App\Http\Dispatcher;
use App\Http\HttpKernel;
use PHPUnit\Framework\MockObject\Invokable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tal\ServerRequest;
use Tal\ServerResponse;
use Test\TestCase;
use Mockery as m;

class DispatcherTest extends TestCase
{
    /** @test */
    public function throwsWhenCalledOnEmptyQueue()
    {
        $dispatcher = new Dispatcher([], [HttpKernel::class, 'getHandler']);

        self::expectException(\LogicException::class);
        self::expectExceptionMessage('Queue is empty');

        $dispatcher->handle(new ServerRequest('GET', '/'));
    }

    /** @test */
    public function usesMiddlewaresAndRequestHandlers()
    {
        $middleware = m::mock(MiddlewareInterface::class);
        $requestHandler = m::mock(RequestHandlerInterface::class);
        $httpKernel = m::mock(HttpKernel::class);
        $request = new ServerRequest('GET', '/');
        $response = new ServerResponse();

        $dispatcher = new Dispatcher([
            $middleware,
            $requestHandler
        ], [$httpKernel, 'getHandler']);

        $httpKernel->shouldNotReceive('getHandler');
        $middleware->shouldReceive('process')->with($request, $dispatcher)
            ->once()->andReturnUsing(function (RequestInterface $request, RequestHandlerInterface $handler) {
                return $handler->handle($request);
            })->ordered();
        $requestHandler->shouldReceive('handle')->with($request)
            ->once()->andReturn($response)->ordered();

        $result = $dispatcher->handle($request);

        self::assertSame($response, $result);
    }

    /** @test */
    public function resolvesHandlerWithResolver()
    {
        $httpKernel = m::mock(HttpKernel::class);
        $errorController = new ErrorController('unexpectedError');

        $dispatcher = new Dispatcher([
            'unexpectedError@ErrorController',
        ], [$httpKernel, 'getHandler']);

        $httpKernel->shouldReceive('getHandler')->with('unexpectedError@ErrorController')
            ->once()->andReturn($errorController);

        $response = $dispatcher->handle(new ServerRequest('GET', '/'));
    }

    public function usesCallablesWithRequestAndHandler()
    {
//        $middleware = m::mock(Invokable::class)
    }
}

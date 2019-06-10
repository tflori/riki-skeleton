<?php

namespace Test\Unit\Http;

use App\Http\Controller\ErrorController;
use App\Http\RequestHandler;
use Mockery as m;
use Tal\ServerRequest;
use Tal\ServerResponse;
use Test\TestCase;

class ControllerDispatcherTest extends TestCase
{
    /** @test */
    public function throwsWhenMethodNotFound()
    {
        $handler = new RequestHandler($this->app, ErrorController::class, 'anyMethod');

        self::expectException(\Exception::class);
        self::expectExceptionMessage('Action anyMethod is unknown in ' . ErrorController::class);

        $handler->handle(m::mock(ServerRequest::class));
    }

    /** @test */
    public function callsActionWithArguments()
    {
        $handler = new RequestHandler($this->app, ErrorController::class, 'unexpectedError');
        $controller = m::mock(ErrorController::class, [$this->app])->makePartial();
        $this->app->instance(ErrorController::class, $controller);
        $request = m::mock(ServerRequest::class);
        $exception = new \Exception('Foo Bar');

        $request->shouldReceive('getAttribute')->with('arguments')
            ->once()->andReturn(['exception' => $exception])->ordered();
        $controller->shouldReceive('unexpectedError')->with($request, $exception)
            ->once()->andReturn(m::mock(ServerResponse::class))->ordered();

        $handler->handle($request);
    }
}

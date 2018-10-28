<?php

namespace Test\Unit\Http\Controller;

use Mockery as m;
use App\Http\Controller\ErrorController;
use Tal\ServerRequest;
use Tal\ServerResponse;
use Test\TestCase;

class AbstractControllerTest extends TestCase
{
    /** @test */
    public function throwsWhenActionNotFound()
    {
        $controller = new ErrorController('anyMethod');

        self::expectException(\Exception::class);
        self::expectExceptionMessage('Action anyMethod is unknown in ' . ErrorController::class);

        $controller->handle(m::mock(ServerRequest::class));
    }

    /** @test */
    public function callsActionWithArguments()
    {
        $controller = m::mock(ErrorController::class, ['unexpectedError'])->makePartial();
        $request = m::mock(ServerRequest::class);
        $exception = new \Exception('Foo Bar');

        $request->shouldReceive('getAttribute')->with('arguments')
            ->once()->andReturn(['exception' => $exception])->ordered();
        $controller->shouldReceive('unexpectedError')->with($exception)
            ->once()->andReturn(m::mock(ServerResponse::class))->ordered();

        $controller->handle($request);
    }
}

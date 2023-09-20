<?php

namespace Test\Unit\Http\Controller;

use App\Http\Controller\ErrorController;
use Tal\ServerRequest;
use Test\TestCase;
use Mockery as m;

class ErrorControllerTest extends TestCase
{
    /** @test */
    public function returns500Response()
    {
        $request = new ServerRequest('POST', '/any/path');
        $errorController = new ErrorController($this->app);

        self::assertSame(500, $errorController->unexpectedError($request)->getStatusCode());
    }

    /** @test */
    public function rendersUnexpectedError()
    {
        $request = new ServerRequest('POST', '/any/path');
        $errorController = new ErrorController($this->app);

        $body = $errorController->unexpectedError($request)->getBody()->getContents();

        self::assertStringContainsString('Unexpected Error', $body);
    }

    /** @test */
    public function returns404Response()
    {
        $errorController = new ErrorController($this->app);
        $request = new ServerRequest('GET', '/any/path');

        self::assertSame(404, $errorController->notFound($request)->getStatusCode());
    }

    /** @test */
    public function rendersNotFoundError()
    {
        $errorController = new ErrorController($this->app);
        $request = new ServerRequest('GET', '/any/path');

        $body = $errorController->notFound($request)->getBody()->getContents();

        self::assertStringContainsString('File Not Found', $body);
        self::assertStringContainsString('/any/path', $body);
    }

    /** @test */
    public function returns405Response()
    {
        $errorController = new ErrorController($this->app);
        $request = (new ServerRequest('POST', '/any/path'));

        self::assertSame(405, $errorController->methodNotAllowed($request, ['GET'])->getStatusCode());
    }

    /** @test */
    public function rendersMethodNotAllowed()
    {
        $errorController = new ErrorController($this->app);
        $request = (new ServerRequest('POST', '/any/path'));

        $body = $errorController->methodNotAllowed($request, ['GET'])->getBody()->getContents();

        self::assertStringContainsString('Method Not Allowed', $body);
        self::assertStringContainsString('/any/path', $body);
        self::assertStringContainsString('GET', $body);
    }
}

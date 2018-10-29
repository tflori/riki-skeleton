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
        $errorController = new ErrorController();

        self::assertSame(500, $errorController->unexpectedError()->getStatusCode());
    }

    /** @test */
    public function rendersUnexpectedError()
    {
        $errorController = new ErrorController();

        $body = $errorController->unexpectedError()->getBody()->getContents();

        self::assertContains('Unexpected Error', $body);
    }

    /** @test */
    public function returns404Response()
    {
        $errorController = new ErrorController('notFound');
        $request = new ServerRequest('GET', '/any/path');

        self::assertSame(404, $errorController->handle($request)->getStatusCode());
    }

    /** @test */
    public function rendersNotFoundError()
    {
        $errorController = new ErrorController('notFound');
        $request = new ServerRequest('GET', '/any/path');

        $body = $errorController->handle($request)->getBody()->getContents();

        self::assertContains('File Not Found', $body);
        self::assertContains('/any/path', $body);
    }

    /** @test */
    public function returns405Response()
    {
        $errorController = new ErrorController('methodNotAllowed');
        $request = (new ServerRequest('POST', '/any/path'))
            ->withAttribute('arguments', ['allowedMethods' => ['GET']]);

        self::assertSame(405, $errorController->handle($request)->getStatusCode());
    }

    /** @test */
    public function rendersMethodNotAllowed()
    {
        $errorController = new ErrorController('methodNotAllowed');
        $request = (new ServerRequest('POST', '/any/path'))
            ->withAttribute('arguments', ['allowedMethods' => ['GET']]);

        $body = $errorController->handle($request)->getBody()->getContents();

        self::assertContains('Method Not Allowed', $body);
        self::assertContains('/any/path', $body);
        self::assertContains('GET', $body);
    }
}

<?php

namespace App;

use Http\HttpRequest;
use Http\HttpResponse;
use Http\Request;
use Http\Response;
use Whoops;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

class Main
{
    /**
     * Bootstrap your application for a http request
     */
    public function bootstrapHttp()
    {
        DI::init();
        if (DI::environment()->canShowErrors()) {
            $this->initWhoops([new PrettyPageHandler()]);
        } else {
            $this->initWhoops([function () {
                (new ErrorController())->internalError()->send();
            }]);
        }

        // @todo initialize your routes here
    }

    /**
     * Bootstrap your application for cli
     */
    public function bootstrapCli()
    {
        DI::init();
        $this->initWhoops([new PlainTextHandler()]);

        $getOpt = DI::getOpt();
        // @todo initialize your commands here
    }

    protected function initWhoops(array $handlers = [])
    {
        $whoops = new Whoops\Run();
        $handler = new PlainTextHandler();
        $handler->setLogger(DI::logger());
        $handler->loggerOnly(true);
        $whoops->pushHandler($handler);
        foreach ($handlers as $handler) {
            $whoops->pushHandler($handler);
        }
        $whoops->register();
    }

    /**
     * Run the http application for $request
     *
     * *NOTE*: The request parameter will get mandatory till version 1.0
     *
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    public function runHttp(Request $request = null): Response
    {
        if (!$request) {
            // @todo I don't like it - the HttpRequest class should have a createFromSuperGlobals
            $request = new HttpRequest($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
        }

        // @todo route the request to a proper controller that returns a response
        return new HttpResponse();
    }

    public function runCli($args = null)
    {

    }
}

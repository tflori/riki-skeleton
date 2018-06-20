<?php

namespace App\Http;

use App\Application;
use Http\HttpRequest;
use Http\HttpResponse;
use Http\Request;
use Http\Response;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

class Kernel extends \Riki\Kernel
{
    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'initWhoops']
        );
    }

    public function handle(Request $request = null): Response
    {
        if (!$request) {
            // @todo I don't like it - the HttpRequest class should have a createFromSuperGlobals
            $request = new HttpRequest($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
        }

        throw new \InvalidArgumentException('What?');

        // @todo route the request to a proper controller that returns a response
        return new HttpResponse();
    }

    public function initWhoops(Application $app): bool
    {
        $previousHandler = $app->whoops->popHandler();
        if ($app->environment->canShowErrors()) {
            $handler = new PrettyPageHandler();
            // $handler->setEditor(...)
            $app->whoops->pushHandler($handler);
        } else {
            $app->whoops->pushHandler(function () use ($app) {
                $response = $app->response;
                $response->setContent('<h1>Something went wrong</h1>');
                $response->setStatusCode(500);
                $response->send();
                return Handler::DONE;
            });
        }
        $app->whoops->pushHandler($previousHandler);

        return true;
    }
}

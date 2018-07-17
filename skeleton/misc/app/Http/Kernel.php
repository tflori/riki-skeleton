<?php

namespace App\Http;

use App\Application;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

class Kernel extends \App\Kernel
{
    public function __construct()
    {
        // $this->addBootstrappers();
    }

    // @todo this should return a response object
    public function handle(\Riki\Application $app, $request = null): array
    {
        if (!$request) {
            // @todo create a request object
            // During tests we don't create a request object from super globals
            // @codeCoverageIgnoreStart
            $request = ['uri' => $_SERVER['REQUEST_URI'], 'get' => $_GET, 'post' => $_POST, 'files' => $_FILES];
            // @codeCoverageIgnoreEnd
        }

        // @todo route the request to a proper controller that returns a response
        return [
            'headers' => ['HTTP/1.1 404 Not Found'],
            'content' => '<h1>File Not Found</h1>',
        ];
    }

    public function getErrorHandlers(Application $app): array
    {
        if ($app->environment->canShowErrors()) {
            $handler = new PrettyPageHandler();
            // $handler->setEditor(...)
            return [$handler];
        } else {
            return [function () {
                // This code will not be executed in tests
                // @codeCoverageIgnoreStart
                // @todo show the default error page
                if (!headers_sent()) {
                    header('HTTP/1.1 500 Internal Server Error');
                    header('Content-Type: text/html; charset=utf-8');
                }
                echo '<h1>Something went wrong</h1>';
                return Handler::QUIT;
                // @codeCoverageIgnoreEnd
            }];
        }
    }
}

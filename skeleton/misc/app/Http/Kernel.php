<?php

namespace App\Http;

use App\Application;
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

    public function handle($request = null): array // @todo this should return a response object
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

    public function initWhoops(Application $app): bool
    {
        if ($app->environment->canShowErrors()) {
            $handler = new PrettyPageHandler();
            // $handler->setEditor(...)
            $app->appendWhoopsHandler($handler);
        } else {
            $app->appendWhoopsHandler(function () {
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
            });
        }

        return true;
    }
}

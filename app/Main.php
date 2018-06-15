<?php

namespace App;

use Http\HttpRequest;
use Http\HttpResponse;
use Http\Request;
use Http\Response;

class Main
{
    public function bootstrapHttp()
    {
    }

    public function bootstrapCli()
    {
    }

    public function runHttp(Request $request = null): Response
    {
        if (!$request) {
            // @todo i don't like it
            $request = new HttpRequest($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
        }
        $response = new HttpResponse();
        $response->setContent('Hello world!');
        return $response;
    }

    public function runCli($args = null)
    {
    }
}

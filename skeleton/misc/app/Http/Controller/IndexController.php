<?php

namespace App\Http\Controller;

use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tal\ServerResponse;

class IndexController implements RequestHandlerInterface
{
    /**
     * Handle the request and return a response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = new ServerResponse(200);
        $response->setBody(stream_for('<!DOCTYPE html><html><head><title>index</title></head></html>'));
        return $response;
    }
}

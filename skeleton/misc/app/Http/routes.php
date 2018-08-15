<?php

use Psr\Http\Server\RequestHandlerInterface;

/** @var \App\Http\Router\MiddlewareRouteCollector $r */
$r = $router;

$r->addHandler(function ($request, RequestHandlerInterface $next) {
    $response = $next->handle($request);
    return $response->withHeader('X-Handeled-By', 'closure middleware');
});

$r->get('/error', ['ErrorController', 'unexpectedError']);

$r->get('/', 'IndexController');

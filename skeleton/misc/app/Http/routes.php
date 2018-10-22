<?php
/**
 * Define your routes here.
 *
 * Handlers should always be class names, callable like ['class', 'method'] or the form 'action@Controller'. You can
 * also use closure handlers but keep in mind that they can not be serialized and therefore you are not able to cache
 * routes.
 *
 * You may want to split this file with groups and includes or load routes from annotations, or from several files.
 *
 * @see \App\Http\HttpKernel::collectRoutes
 */

use App\Application;
use App\Http\Router\MiddlewareRouteCollector;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Server\RequestHandlerInterface;
use Tal\ServerRequest;
use Tal\ServerResponse;

/** @var MiddlewareRouteCollector $router */
$r = $router;

// example routes - comment them out and use as reference
$r->addHandler(function (ServerRequest $request, RequestHandlerInterface $next) {
    return $next->handle($request)
        ->withHeader('X-Handeled-By', 'closure middleware');
});

$r->addGroup('/foo', function (MiddlewareRouteCollector $router) {
    $router->addHandler(function (ServerRequest $request, RequestHandlerInterface $next) {
        return $next->handle($request)
            ->withHeader('X-foo', 'another closure handler');
    });

    $router->get('/bar', function (ServerRequest $request) {
        /** @var ServerResponse $response */
        $response = Application::app()->make(ServerResponse::class);
        return $response->withBody(stream_for('<h1>Bazinga!</h1>'));
    });
});

$r->get('/error', ['ErrorController', 'unexpectedError']);

$r->get('/', 'IndexController');

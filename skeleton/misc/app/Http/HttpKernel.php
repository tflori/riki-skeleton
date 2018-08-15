<?php

namespace App\Http;

use App\Application;
use App\Http\Controller\AbstractController;
use App\Http\Controller\ErrorController;
use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use App\Http\Router\MiddlewareRouter;
use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tal\ServerRequest;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

class HttpKernel extends \App\Kernel
{
    const CONTROLLER_NAMESPACE = 'App\Http\Controller';

    /** @var MiddlewareRouter */
    protected $router;

    public function __construct()
    {
        $this->addBootstrappers(
            [$this, 'loadRoutes']
        );
    }

    public function handle(ServerRequest $request = null): ResponseInterface
    {
        if (!$request) {
            // During tests we don't create a request object from super globals
            // @codeCoverageIgnoreStart
            $request = ServerRequest::fromGlobals();
            // @codeCoverageIgnoreEnd
        }

        $result = $this->router->dispatch($request->getMethod(), $request->getRelativePath());
        switch ($result[0]) {
            case FastRoute\Dispatcher::FOUND:
                list(, $handler, $arguments) = $result;
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                list(, $allowedMethods, $handler) = $result;
                $handler[] = ['ErrorController', 'methodNotAllowed'];
                $arguments = ['allowedMethods' => $allowedMethods];
                break;
            case FastRoute\Dispatcher::NOT_FOUND:
                list(, $handler) = $result;
                $handler[] = ['ErrorController', 'notFound'];
                break;
        }

        if (isset($arguments)) {
            $request = $request->withAttribute('arguments', $arguments);
        }

        return (new Dispatcher($handler, function ($handler) {
            if (is_callable($handler)) {
                return $handler;
            }

            if (is_array($handler)) {
                $class = array_shift($handler);
                $args = $handler;
            } else {
                $class = $handler;
                $args = [];
            }

            if (!class_exists($class)) {
                if (class_exists(self::CONTROLLER_NAMESPACE . '\\' . $class)) {
                    $class = self::CONTROLLER_NAMESPACE . '\\' . $class;
                } else {
                    throw new \InvalidArgumentException(sprintf('Class %s not found', $class));
                }
            }

            return Application::app()->make($class, ...$args);
        }))->handle($request);
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
                Application::app()->make(ErrorController::class)->unexpectedError()->send();
                return Handler::QUIT;
                // @codeCoverageIgnoreEnd
            }];
        }
    }

    public function loadRoutes(Application $app): bool
    {
        if (!$this->router) {
            // @todo implement caching for $routeCollector->getData()
            $dataGenerator = $app->make(MiddlewareDataGenerator::class);
            $routeParser = $app->make(FastRoute\RouteParser\Std::class);
            $routeCollector = $app->make(MiddlewareRouteCollector::class, $routeParser, $dataGenerator);
            self::collectRoutes($routeCollector);
            $this->router = $app->make(MiddlewareRouter::class, $routeCollector->getData());
        }

        return true;
    }

    protected static function collectRoutes(MiddlewareRouteCollector $router)
    {
        // @todo maybe you want to load different route files or collect them from annotations..
        include __DIR__ . '/routes.php';
        $router->addGroup('/', function () {
        });
    }
}

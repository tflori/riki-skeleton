<?php

namespace App\Http;

use App\Application;
use App\Http\Controller\ErrorController;
use App\Http\Router\MiddlewareDataGenerator;
use App\Http\Router\MiddlewareRouteCollector;
use App\Http\Router\MiddlewareRouter;
use FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tal\ServerRequest;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;

class HttpKernel extends \App\Kernel
{
    const CONTROLLER_NAMESPACE = 'App\Http\Controller';

    /** @var ServerRequest */
    protected static $lastRequest;

    /** @var MiddlewareRouter */
    protected $router;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        // bootstrap the kernel
    }

    public function handle(ServerRequest $request = null): ResponseInterface
    {
        if (!$request) {
            // During tests we don't create a request object from super globals
            // @codeCoverageIgnoreStart
            $request = ServerRequest::fromGlobals();
            // @codeCoverageIgnoreEnd
        }

        self::$lastRequest = $request;

        $handlers = [];
        $arguments = [];
        $result = $this->getRouter()->dispatch($request->getMethod(), $request->getRelativePath());
        switch ($result[0]) {
            case FastRoute\Dispatcher::FOUND:
                list(, $handlers, $arguments) = $result;
                break;

            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                list(, $allowedMethods, $handlers) = $result;
                $handlers[] = [ErrorController::class, 'methodNotAllowed'];
                $arguments = ['allowedMethods' => $allowedMethods];
                break;

            case FastRoute\Dispatcher::NOT_FOUND:
                list(, $handlers) = $result;
                $handlers[] = [ErrorController::class, 'notFound'];
                break;
        }

        if (!empty($arguments)) {
            $request = $request->withAttribute('arguments', $arguments);
        }

        return Application::app()
            ->make(Dispatcher::class, $handlers, [$this, 'getHandler'])
            ->handle($request);
    }

    /**
     * Get a RequestHandler or Middleware for $handler
     *
     * @param string|array|callable $handler
     * @return RequestHandlerInterface|MiddlewareInterface|callable
     */
    public function getHandler($handler)
    {
        if (is_callable($handler)) {
            switch (gettype($handler)) {
                case 'string':
                    if (strpos($handler, '::') === false) {
                        return $handler;
                    }
                    list($class, $method) = explode('::', $handler);
                    break;

                case 'array':
                    if (is_object($handler[0])) {
                        return $handler;
                    }
                    list($class, $method) = $handler;
                    break;

                default:
                case 'object':
                    return $handler;
            }
            // for static calls we have to check more... because is callable returns true for non static methods
            if (class_exists($class)) {
                $class = new \ReflectionClass($class);
                $method = $class->getMethod($method);
                if (!$class->isAbstract() && $method->isStatic() && $method->isPublic() && !$method->isAbstract()) {
                    return $handler;
                }
            }
        }

        if (is_array($handler)) {
            $class = array_shift($handler);
            $args = $handler;
        } elseif (is_string($handler)) {
            $class = $handler;
            $args = [];
            if (1 < $pos = strpos($handler, '@')) {
                $class = substr($handler, $pos + 1);
                $args[] = substr($handler, 0, $pos);
            }
        }

        if (!class_exists($class)) {
            if (!class_exists(self::CONTROLLER_NAMESPACE . '\\' . $class)) {
                throw new \InvalidArgumentException(sprintf('Class %s not found', $class));
            }
            $class = self::CONTROLLER_NAMESPACE . '\\' . $class;
        }

        if (substr($class, 0, strlen(self::CONTROLLER_NAMESPACE)) === self::CONTROLLER_NAMESPACE) {
            array_unshift($args, $this->app);
        }

        return $this->app->make($class, ...$args);
    }

    public function getErrorHandlers(): array
    {
        if ($this->app->environment->canShowErrors()) {
            $handler = new PrettyPageHandler();
            // $handler->setEditor(...)
            return [$handler];
        } else {
            return [function ($exception = null) {
                $request = (self::$lastRequest ?? ServerRequest::fromGlobals())
                    ->withAttribute('arguments', ['exception' => $exception]);
                /** @var ErrorController $errorController */
                $errorController = self::getHandler([ErrorController::class, 'unexpectedError']);
                $errorController->handle($request)->send();
                return Handler::QUIT;
            }];
        }
    }

    public function getRouter(): MiddlewareRouter
    {
        if (!$this->router) {
            // @todo implement caching for $routeCollector->getData()
            $dataGenerator = $this->app->make(MiddlewareDataGenerator::class);
            $routeParser = $this->app->make(FastRoute\RouteParser\Std::class);
            $routeCollector = $this->app->make(MiddlewareRouteCollector::class, $routeParser, $dataGenerator);
            self::collectRoutes($routeCollector);
            $this->router = $this->app->make(MiddlewareRouter::class, $routeCollector->getData());
        }

        return $this->router;
    }

    protected static function collectRoutes(MiddlewareRouteCollector $router)
    {
        // @todo maybe you want to load different route files or collect them from annotations..
        include __DIR__ . '/routes.php';
        $router->addGroup('/', function () {
            // this ensures that the handler on root level are loaded for non matching routes
        });
    }
}

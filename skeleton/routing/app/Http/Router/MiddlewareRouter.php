<?php

namespace App\Http\Router;

use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased;

class MiddlewareRouter extends GroupCountBased
{
    protected $groups;

    public function __construct($data)
    {
        $this->groups = array_pop($data);
        parent::__construct($data);
    }

    public function dispatch($httpMethod, $uri)
    {
        $result = parent::dispatch($httpMethod, $uri);

        if ($result[0] === Dispatcher::NOT_FOUND || $result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            $result[] = preg_match($this->groups['regex'], $uri, $matches) ?
                $this->groups['groupMap'][count($matches)] : [];
        }

        return $result;
    }
}

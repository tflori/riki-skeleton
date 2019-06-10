<?php

namespace Test\Http;

use App\Http\HttpKernel;
use Tal\Psr7Extended\ServerResponseInterface;
use Tal\ServerRequest;

abstract class TestCase extends \Test\TestCase
{
    protected function get(string $uri, array $query = []): ServerResponseInterface
    {
        $request = (new ServerRequest('get', $uri, []))->withQueryParams($query);
        $kernel = new HttpKernel($this->app);
        return $this->app->run($kernel, $request);
    }
}

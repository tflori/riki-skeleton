<?php

namespace Test\Http;

use App\Http\HttpKernel;

abstract class TestCase extends \Test\TestCase
{
    protected function get(string $uri, array $query = [])
    {
        $request = ['uri' => $uri, 'get' => $query, 'post' => [], 'files' => []];
        $kernel = new HttpKernel($this->app);
        return $this->app->run($kernel, $request);
    }
}

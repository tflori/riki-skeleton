<?php

namespace Test\Http;

use App\Http\Kernel;

abstract class TestCase extends \Test\TestCase
{
    protected function get(string $uri, array $query = [])
    {
        $request = ['uri' => $uri, 'get' => $query, 'post' => [], 'files' => []];
        $kernel = new Kernel();
        $this->bootstrap(...$kernel->getBootstrappers());
        return $kernel->handle($request);
    }

    protected function bootstrap(callable ...$bootstrappers)
    {
        foreach ($bootstrappers as $bootstrapper) {
            call_user_func($bootstrapper, $this->app);
        }
    }
}

<?php

namespace Test\Unit\Http;

use App\Http\HttpKernel;
use Test\TestCase;
use Whoops\Handler\PrettyPageHandler;

class HttpKernelTest extends TestCase
{
    /** @test */
    public function definesACustomErrorHandler()
    {
        $kernel = new HttpKernel($this->app);
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(false);

        $result = $kernel->getErrorHandlers();

        self::assertInstanceOf(\Closure::class, $result[0]);
    }

    /** @test */
    public function definesAPrettyPageHandler()
    {
        $kernel = new HttpKernel($this->app);
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(true);

        $result = $kernel->getErrorHandlers();

        self::assertInstanceOf(PrettyPageHandler::class, $result[0]);
    }
}

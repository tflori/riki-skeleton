<?php

namespace Test\Unit\Http;

use App\Http\Kernel;
use Mockery as m;
use Test\TestCase;
use Whoops\Handler\PrettyPageHandler;

class KernelTest extends TestCase
{
    /** @test */
    public function initWhoopsReturnsTrue()
    {
        $kernel = new Kernel();
        $result = $kernel->initWhoops($this->app);

        self::assertTrue($result);
    }

    /** @test */
    public function initWhoopsAddsClosureHandler()
    {
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(false);
        $this->app->shouldReceive('appendWhoopsHandler')
            ->with(m::type(\Closure::class))->once();

        $kernel = new Kernel();
        $kernel->initWhoops($this->app);
    }

    /** @test */
    public function initWhoopsAddsPrettyPageHandler()
    {
        $this->app->environment->shouldReceive('canShowErrors')->andReturn(true);
        $this->app->shouldReceive('appendWhoopsHandler')
            ->with(m::type(PrettyPageHandler::class))->once();

        $kernel = new Kernel();
        $kernel->initWhoops($this->app);
    }
}

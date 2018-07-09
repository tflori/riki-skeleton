<?php

namespace Test\Unit\Cli;

use App\Cli\Kernel;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;

class KernelTest extends TestCase
{
    /** @test */
    public function initWhoopsReturnsTrue()
    {
        $kernel = new Kernel($this->app);
        $result = $kernel->initWhoops($this->app);

        self::assertTrue($result);
    }

    public function appendsAPlainTextHandler()
    {
        $this->app->shouldReceive('appendWhoopsHandler')
            ->with(m::type(PlainTextHandler::class))->once();

        $kernel = new Kernel($this->app);
        $kernel->initWhoops($this->app);
    }
}

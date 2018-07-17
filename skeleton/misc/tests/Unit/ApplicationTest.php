<?php

namespace Test\Unit;

use App\Kernel;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;
use Mockery as m;

class ApplicationTest extends TestCase
{
    /** @test */
    public function registersErrorHandler()
    {
         $this->mocks['whoops']->shouldReceive('register')->with()
           ->once()->andReturnSelf();

         $this->app->initWhoops();
    }

    /** @test */
    public function definesAnErrorHandlerForLogging()
    {
        $handler = new PlainTextHandler($this->app->logger);
        $handler->loggerOnly(true);

        $this->app->initWhoops();

        self::assertEquals($handler, $this->app->get('whoops')->popHandler());
    }

    /** @test */
    public function prependsAndRemovesHandlerFromKernel()
    {
        $handlersBefore = $this->app->whoops->getHandlers();
        $kernelHandlers = [new PlainTextHandler()];
        $kernel = m::mock(Kernel::class);
        $kernel->shouldReceive('getBootstrappers')->andReturn([]);
        $kernel->shouldReceive('getErrorHandlers')->with($this->app)
            ->once()->andReturn($kernelHandlers);


        $kernel->shouldReceive('handle')->with($this->app)
            ->once()->andReturnUsing(function () use ($handlersBefore, $kernelHandlers) {
                self::assertSame(array_merge($kernelHandlers, $handlersBefore), $this->app->whoops->getHandlers());
            });

        $this->app->run($kernel);

        self::assertSame($handlersBefore, $this->app->whoops->getHandlers());
    }
}

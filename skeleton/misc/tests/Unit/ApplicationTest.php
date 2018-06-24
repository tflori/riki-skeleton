<?php

namespace Test\Unit;

use Test\TestCase;

class ApplicationTest extends TestCase
{
    /** @test */
    public function prependsTheHandler()
    {
        // reset mocking of appendWhoopsHandler
        $this->app->shouldReceive('appendWhoopsHandler')->passthru()->byDefault();
        $whoops = $this->mocks['whoops'];
        $whoops->shouldReceive('getHandlers')->with()
            ->once()->andReturn(['is_int', 'is_bool']);
        $whoops->shouldReceive('clearHandlers')->with()
            ->once()->andReturnSelf();

        $whoops->shouldReceive('pushHandler')->with('is_string')
            ->ordered('push')->once();
        $whoops->shouldReceive('pushHandler')->with('is_int')
            ->ordered('push')->once();
        $whoops->shouldReceive('pushHandler')->with('is_bool')
            ->ordered('push')->once();

        $this->app->appendWhoopsHandler('is_string');
    }
}

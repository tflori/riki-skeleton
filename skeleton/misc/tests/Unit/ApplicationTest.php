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

        $this->whoops->shouldReceive('getHandlers')->with()
            ->once()->andReturn(['is_int', 'is_bool']);
        $this->whoops->shouldReceive('clearHandlers')->with()
            ->once()->andReturnSelf();

        $this->whoops->shouldReceive('pushHandler')->with('is_string')
            ->ordered('push')->once();
        $this->whoops->shouldReceive('pushHandler')->with('is_int')
            ->ordered('push')->once();
        $this->whoops->shouldReceive('pushHandler')->with('is_bool')
            ->ordered('push')->once();

        $this->app->appendWhoopsHandler('is_string');
    }
}

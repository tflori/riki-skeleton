<?php

namespace Test;

use App\Application;
use App\Environment;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\Mock;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var Application|Mock */
    protected $app;

    protected function setUp()
    {
        parent::setUp();
        $this->app = m::mock(Application::class . '[appendWhoopsHandler]', [realpath(__DIR__ . '/..')])->makePartial();
        $this->app->shouldReceive('appendWhoopsHandler')->withAnyArgs()->andReturnNull()->byDefault();

        $this->app->instance('environment', m::mock(Environment::class)->makePartial());
        $this->app->alias('environment', Environment::class);
    }
}

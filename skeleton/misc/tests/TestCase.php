<?php

namespace Test;

use App\Application;
use App\Environment;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;
use Whoops\Run;

abstract class TestCase extends MockeryTestCase
{
    /** @var Application|m\Mock */
    protected $app;

    /** @var Run|m\Mock */
    protected $whoops;

    protected function setUp()
    {
        parent::setUp();
        /** @var @var Application|m\Mock $app */
        $app = $this->app = m::mock(Application::class . '[appendWhoopsHandler]', [realpath(__DIR__ . '/..')]);
        $app->shouldReceive('appendWhoopsHandler')->withAnyArgs()->andReturnNull()->byDefault();
        $property = (new \ReflectionClass($app))->getProperty('whoops');
        $property->setAccessible(true);
        $property->setValue($app, $this->whoops = m::mock(new Run()));
        $property->setAccessible(false);

        $app->instance('environment', m::mock(Environment::class)->makePartial());
        $app->alias('environment', Environment::class);
    }
}

<?php

namespace Test\Unit;

use App\Config;
use Monolog\Logger;
use Test\TestCase;
use Mockery as m;

class ConfigTest extends TestCase
{
    /** @test */
    public function setsTheLogLevelFromEnv()
    {
        /** @var Config|m\Mock $config */
        $config = m::mock(Config::class)->makePartial();
        $config->shouldReceive('env')->with('LOG_LEVEL', Logger::WARNING)
            ->once()->andReturn('debug');

        $config->__construct($this->mocks['environment']);

        self::assertSame(Logger::DEBUG, $config->logLevel);
    }
}

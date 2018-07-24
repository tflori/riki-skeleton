<?php

namespace Test\Unit\Cli;

use App\Cli\CliKernel;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;

class KernelTest extends TestCase
{
    /** @test */
    public function definesAPlainTextHandler()
    {
        $kernel = new CliKernel();

        $result = $kernel->getErrorHandlers($this->app);

        self::assertInstanceOf(PlainTextHandler::class, $result[0]);
    }
}

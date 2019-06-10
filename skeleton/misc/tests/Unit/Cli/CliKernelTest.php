<?php

namespace Test\Unit\Cli;

use App\Cli\CliKernel;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;

class CliKernelTest extends TestCase
{
    /** @test */
    public function definesAPlainTextHandler()
    {
        $kernel = new CliKernel($this->app);

        $result = $kernel->getErrorHandlers();

        self::assertInstanceOf(PlainTextHandler::class, $result[0]);
    }
}

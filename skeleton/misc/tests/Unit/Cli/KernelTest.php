<?php

namespace Test\Unit\Cli;

use App\Cli\Kernel;
use Test\TestCase;
use Whoops\Handler\PlainTextHandler;

class KernelTest extends TestCase
{
    /** @test */
    public function definesAPlainTextHandler()
    {
        $kernel = new Kernel();

        $result = $kernel->getErrorHandlers($this->app);

        self::assertInstanceOf(PlainTextHandler::class, $result[0]);
    }
}

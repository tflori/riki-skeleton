<?php

namespace Test\Cli;

class NotFoundTest extends TestCase
{
    /** @test */
    public function showsGetOptHelp()
    {
        $this->mocks['getOpt']->shouldReceive('getHelpText')->with()
            ->once()->andReturn('GetOpts Help Text');

        $result = $this->start('any:command');

        self::assertSame(0, $result['returnVar']);
        self::assertSame('GetOpts Help Text', $result['output']);
    }
}

<?php

namespace Test\Cli;

class NotFoundTest extends TestCase
{
    /** @test */
    public function showsCommandNotFound()
    {
        $result = $this->start(['any:command']);

        self::assertSame(0, $result['returnVar']);
        self::assertSame('Command not found' . PHP_EOL, $result['output']);
    }
}

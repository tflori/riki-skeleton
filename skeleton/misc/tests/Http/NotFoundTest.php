<?php

namespace Test\Http;

class NotFoundTest extends TestCase
{
    /** @test */
    public function returnsThe404Page()
    {
        $response = $this->get('/any/route');

        self::assertSame('HTTP/1.1 404 Not Found', $response['headers'][0]);
        self::assertStringContainsString('<h1>File Not Found</h1>', $response['content']);
    }
}

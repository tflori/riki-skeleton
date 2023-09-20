<?php

namespace Test\Http;

class NotFoundTest extends TestCase
{
    /** @test */
    public function returnsThe404Page()
    {
        $response = $this->get('/any/route');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('<h4>File Not Found</h4>', $response->getBody()->getContents());
    }
}

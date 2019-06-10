<?php

namespace Test\Example;

use Tal\ServerResponse;

class CustomController
{
    public static function doSomething()
    {
        return new ServerResponse(200);
    }
}

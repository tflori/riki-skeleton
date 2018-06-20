<?php

namespace App;

use GetOpt\GetOpt;
use Http\Response;
use Monolog\Logger;
use Riki\Environment;

/**
 * @method static Config config()
 * @method static Environment environment()
 * @method static Logger logger()
 * @method static Response response()
 */
class DI extends \DependencyInjector\DI
{
    public static function init()
    {
        self::registerNamespace('App\Factory');
        
        self::share('getOpt', GetOpt::class);
        
        self::alias('environment', Environment::class);
        self::alias('config', Config::class);
        self::alias('logger', Logger::class);
        self::alias('response', Response::class);
        self::alias('getOpt', GetOpt::class);
    }
}

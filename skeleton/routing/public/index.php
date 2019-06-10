<?php

use App\Application;
use App\Http\HttpKernel;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(realPath(__DIR__ . '/..'));
$kernel = new HttpKernel($app);
/** @var \Tal\Psr7Extended\ServerResponseInterface $response */
$response = $app->run($kernel);


$response->send();

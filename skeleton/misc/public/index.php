<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \App\Application(realPath(__DIR__ . '/..'));
$kernel = new \App\Http\HttpKernel();
/** @var \Tal\Psr7Extended\ServerResponseInterface $response */
$response = $app->run($kernel);


$response->send();

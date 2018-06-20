<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \App\Application(realPath(__DIR__ . '/..'));
$kernel = new \App\Http\Kernel();
$response = $app->run($kernel);

$response->send();

<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new App\Main;
$app->bootstrapHttp();
$response = $app->runHttp();

$response->send();

<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new App\Main;
$app->bootstrapHttp();
$response = $app->run(new \Http\HttpRequest($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, 'php://input'));

$response->send();

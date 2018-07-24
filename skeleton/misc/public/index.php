<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \App\Application(realPath(__DIR__ . '/..'));
$kernel = new \App\Http\HttpKernel();
$response = $app->run($kernel);

// @todo send the response
foreach ($response['headers'] as $header) {
    header($header);
}
echo $response['content'];

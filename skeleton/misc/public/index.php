<?php

use App\Application;
use App\Http\HttpKernel;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(realPath(__DIR__ . '/..'));
$kernel = new HttpKernel($app);
$response = $app->run($kernel);

// @todo send the response
foreach ($response['headers'] as $header) {
    header($header);
}
echo $response['content'];

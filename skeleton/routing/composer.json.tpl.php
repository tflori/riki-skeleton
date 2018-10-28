<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

// add required dependencies
$content->mergeJson([
    'require' => [
        'tflori/tal' => '^1.0.1',
        'nikic/fast-route' => '^1.3.0',
        'psr/http-server-handler' => '^1.0.0',
        'psr/http-server-middleware' => '^1.0.0',
    ]
]);

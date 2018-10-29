<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->mergeJson([
    'require' => [
        'tflori/riki-framework' => 'dev-master',
    ],
    'require-dev' => [
        'squizlabs/php_codesniffer' => '^3.3',
    ],
    'scripts' => [
        'code-style' => 'phpcs --standard=PSR2 app && phpcs --standard=PSR2 --ignore=example tests',
    ],
]);

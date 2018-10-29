<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->mergeJson([
    'scripts' => [
        'start' => 'docker-compose up -d',
        'stop' => 'docker-compose stop',
        'cli' => 'docker-compose exec -T php ' . $binaryFile,
        'debug-cli' => 'docker-compose exec -T php debug bin/' . $binaryFile,
        'test' => 'docker-compose exec -T php phpunit',
        'debug-test' => 'docker-compose exec -T php debug bin/phpunit',
    ],
]);

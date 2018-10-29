<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

// update installation instructions
$content->prepend(
    '```console',
    'Copy the docker-compose.example.yml to docker-compose.yml and adjust it to your needs.' . PHP_EOL
)
    ->prepend('\$ composer install', '$ docker-compose pull')
    ->replace('\$ composer install', '$ docker-compose run composer install')
    ->prepend('\$ composer start', '$ docker-compose build');

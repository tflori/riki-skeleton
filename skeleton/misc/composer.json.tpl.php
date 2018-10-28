<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->json([
  'name' => $projectName,
  'description' => '',
  'type' => 'project',
  'license' => 'proprietary',
  'config' => [
    'bin-dir' => 'bin',
  ],
  'require' => [
    'php' => '^7.1',
    'ext-mbstring' => '*',
    'filp/whoops' => '^2.2',
    'monolog/monolog' => '^1.9',
    'tflori/riki-framework' => '1.0.0-alpha.3',
  ],
  'autoload' => [
    'psr-4' => [
      'App\\' => 'app',
      $sourceNamespace . '\\' => 'src',
    ]
  ],
  'require-dev' => [
    'phpunit/phpunit' => '^7.2',
    'mockery/mockery' => '^1.1',
  ],
  'autoload-dev' => [
    'psr-4' => [
      'Test\\' => 'tests',
    ]
  ],
  'scripts' => [
    'start' => 'php -S localhost:8080 -t public public/routing.php',
    'cli' => $binaryFile,
    'test' => 'phpunit',
  ]
]);

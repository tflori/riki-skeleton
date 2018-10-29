<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->prepend('use Monolog\\\Logger;', 'use Hugga\\\Console;')
    ->append(' \* @method static Logger logger\(\)', ' * @method static Console console()')
    ->append(' \* @property-read Logger \$logger', ' * @property-read Console $console');

<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

$content->prepend(
    '        </whitelist>',
    '            <exclude>' . PHP_EOL .
    '                <file>./app/Http/routes.php</file>' . PHP_EOL .
    '            </exclude>'
);

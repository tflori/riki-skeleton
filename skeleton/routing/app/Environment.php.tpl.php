<?php

/** @var Skeleton\Content $content */
/** @var string $projectName */
/** @var string $sourceNamespace */
/** @var string $binaryFile */

// add method resourcePath
$content->addMethod('
    public function resourcePath(string ...$path)
    {
        return $this->path(\'resources\', ...$path);
    }
', 'storagePath');

// add method
$content->addMethod('
    public function viewPath(string ...$path): string
    {
        return $this->resourcePath(\'views\', ...$path);
    }
', 'logPath');

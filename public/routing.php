<?php

$file = __DIR__ . explode('?', $_SERVER['REQUEST_URI'])[0];

if (file_exists($file) && !is_dir($file)) {
    return false;
} else {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    include __DIR__ . '/index.php';
}

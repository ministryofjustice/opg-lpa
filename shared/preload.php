<?php
$sharedClassMap = require __DIR__ . '/vendor/composer/autoload_classmap.php';
$sharedFileMap = array_flip($sharedClassMap);

$appClassMap = require '/app/vendor/composer/autoload_classmap.php';
$appFileMap = array_flip($appClassMap);

$combinedFileMap = array_merge($appFileMap, $sharedFileMap);

foreach ($combinedFileMap as $file => $class) {
    opcache_compile_file($file);
}
?>
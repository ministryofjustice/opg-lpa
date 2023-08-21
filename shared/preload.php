<?php
$appClassMap = require '/app/vendor/composer/autoload_classmap.php';
$appFileMap = array_flip($appClassMap);

foreach ($appFileMap as $file => $class) {
    opcache_compile_file($file);
}
?>
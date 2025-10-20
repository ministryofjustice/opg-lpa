<?php

$directory = new RecursiveDirectoryIterator('/app');
$fullTree = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($fullTree, '/.+((?<!Test)+\.php$)/i', RecursiveRegexIterator::GET_MATCH);

ini_set('memory_limit', '-1');

foreach ($phpFiles as $key => $file) {
    opcache_compile_file($file[0]);
}

<?php

$projectPath = __DIR__ ;

// Declare directories which contains php code
$scanDirectories = [
   $projectPath . '/module/Application/src/',
   $projectPath . '/module/Application/config/',
   $projectPath . '/module/Application/tests/',
   $projectPath . '/phinx.php',
];

// Optionally declare standalone files
$scanFiles = [];

return [
   'composerJsonPath' => $projectPath . '/composer.json',
   'vendorPath' => $projectPath . '/vendor/',
   'scanDirectories' => $scanDirectories,
   'scanFiles'=>$scanFiles
];

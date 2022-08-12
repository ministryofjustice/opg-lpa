<?php

$projectPath = __DIR__ ;

// Declare directories which contains php code
$scanDirectories = [
   $projectPath . '/src/App/src/',
   $projectPath . '/config',
   $projectPath . '/bin',
   $projectPath . '/test',
];

// Optionally declare standalone files
$scanFiles = [];

return [
   'composerJsonPath' => $projectPath . '/composer.json',
   'vendorPath' => $projectPath . '/vendor/',
   'scanDirectories' => $scanDirectories,
   'scanFiles'=>$scanFiles
];

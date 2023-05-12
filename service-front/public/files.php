<?php

$file = "/tmp/cachegrind.out.8125";
$dir    = '/tmp';
$files1 = scandir($dir);

foreach ($files1 as $file) {
    echo "<a href='download.php?file=" . $file . "'>" . $file . "</a><br />";
}
